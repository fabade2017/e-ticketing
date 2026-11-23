<?php

namespace Tests\Feature;

use App\Models\Hospital;
use App\Models\SuiteNotification;
use App\Models\User;
use App\Modules\Supply\Models\SupplyApprovalRequest;
use App\Modules\Supply\Models\SupplyGoodsReceived;
use App\Modules\Supply\Models\SupplyItem;
use App\Modules\Supply\Models\SupplyItemCategory;
use App\Modules\Supply\Models\SupplyReorderRule;
use App\Modules\Supply\Models\SupplyStockBalance;
use App\Modules\Supply\Services\StockLedgerService;
use App\Modules\Supply\Services\SupplyAlertService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SupplyModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_stock_receipt_is_idempotent_and_updates_balance(): void
    {
        $hospital = $this->hospital();
        $actor = $this->moduleUser('WarehouseManager', $hospital);
        $item = SupplyItem::factory()->create();
        $ledger = app(StockLedgerService::class);

        $first = $ledger->receive($item, $hospital->id, 25, $actor, ['idempotency_key' => 'grn-001']);
        $second = $ledger->receive($item, $hospital->id, 25, $actor, ['idempotency_key' => 'grn-001']);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, SupplyGoodsReceived::count());
        $this->assertSame('approved', $first->fresh()->status);
        $this->assertDatabaseHas('supply_stock_balances', [
            'item_id' => $item->id,
            'hospital_id' => $hospital->id,
            'quantity_on_hand' => 25,
        ]);
    }

    public function test_issue_cannot_exceed_available_stock(): void
    {
        $hospital = $this->hospital();
        $actor = $this->moduleUser('WarehouseManager', $hospital);
        $item = SupplyItem::factory()->create();
        SupplyStockBalance::factory()->create([
            'item_id' => $item->id,
            'hospital_id' => $hospital->id,
            'quantity_on_hand' => 5,
        ]);

        try {
            app(StockLedgerService::class)->issue($item, $hospital->id, 10, $actor, [
                'recipient_type' => 'unit',
                'idempotency_key' => 'issue-over',
            ]);
            $this->fail('Expected an insufficient-stock validation error.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('quantity', $exception->errors());
        }

        $this->assertDatabaseHas('supply_stock_balances', [
            'item_id' => $item->id,
            'hospital_id' => $hospital->id,
            'quantity_on_hand' => 5,
        ]);
    }

    public function test_stores_officer_receipt_waits_for_approval_and_approval_updates_balance(): void
    {
        $hospital = $this->hospital();
        $requester = $this->moduleUser('StoresOfficer', $hospital);
        $approver = $this->moduleUser('WarehouseManager', $hospital);
        $item = SupplyItem::factory()->create();
        $ledger = app(StockLedgerService::class);

        $movement = $ledger->receive($item, $hospital->id, 20, $requester, ['idempotency_key' => 'grn-pending']);

        $this->assertSame('pending', $movement->status);
        $approval = SupplyApprovalRequest::firstOrFail();
        $this->assertSame('stock.receipt', $approval->type);
        $this->assertDatabaseMissing('supply_stock_balances', [
            'item_id' => $item->id,
            'hospital_id' => $hospital->id,
        ]);

        $subject = $ledger->approve($approval, $approver);

        $this->assertSame($movement->id, $subject->id);
        $this->assertSame('approved', $subject->fresh()->status);
        $this->assertSame('approved', $approval->fresh()->status);
        $this->assertDatabaseHas('supply_stock_balances', [
            'item_id' => $item->id,
            'hospital_id' => $hospital->id,
            'quantity_on_hand' => 20,
        ]);
    }

    public function test_low_stock_alerts_create_notifications_for_supply_users(): void
    {
        $hospital = $this->hospital();
        $this->moduleUser('StoresOfficer', $hospital);
        $item = SupplyItem::factory()->create();
        SupplyStockBalance::factory()->create([
            'item_id' => $item->id,
            'hospital_id' => $hospital->id,
            'quantity_on_hand' => 2,
        ]);
        SupplyReorderRule::factory()->create([
            'item_id' => $item->id,
            'hospital_id' => $hospital->id,
            'minimum_quantity' => 10,
        ]);

        $result = app(SupplyAlertService::class)->refresh($hospital->id);

        $this->assertSame(1, $result['low_stock']);
        $this->assertTrue(SuiteNotification::where('module', 'supply')->where('type', 'supply.low_stock')->exists());
    }

    public function test_supply_index_renders_for_authorized_user(): void
    {
        $hospital = $this->hospital();
        $user = $this->moduleUser('WarehouseManager', $hospital);

        $this->actingAs($user)->get('/supply')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Supply/Index')
                ->where('categories', fn ($categories) => collect($categories)->pluck('name')->contains('medical'))
            );
    }

    public function test_new_item_uses_selected_active_category(): void
    {
        $hospital = $this->hospital();
        $user = $this->moduleUser('WarehouseManager', $hospital);
        $category = SupplyItemCategory::factory()->create(['name' => 'Surgical Consumables']);

        $this->actingAs($user)->post('/supply/items', [
            'name' => 'Sterile gloves',
            'sku' => 'SKU-CATEGORY-1',
            'supply_item_category_id' => $category->id,
            'unit' => 'box',
        ])->assertRedirect();

        $this->assertDatabaseHas('supply_items', [
            'sku' => 'SKU-CATEGORY-1',
            'category' => 'Surgical Consumables',
            'supply_item_category_id' => $category->id,
        ]);
    }

    public function test_new_item_rejects_inactive_category(): void
    {
        $hospital = $this->hospital();
        $user = $this->moduleUser('WarehouseManager', $hospital);
        $category = SupplyItemCategory::factory()->inactive()->create();

        $this->actingAs($user)->post('/supply/items', [
            'name' => 'Obsolete item',
            'sku' => 'SKU-CATEGORY-2',
            'supply_item_category_id' => $category->id,
            'unit' => 'each',
        ])->assertSessionHasErrors('supply_item_category_id');

        $this->assertDatabaseMissing('supply_items', ['sku' => 'SKU-CATEGORY-2']);
    }

    private function hospital(): Hospital
    {
        return Hospital::factory()->create();
    }

    private function moduleUser(string $role, Hospital $hospital): User
    {
        $user = User::factory()->create(['current_hospital_id' => $hospital->id]);
        $user->assignRole($role);
        $hospital->users()->attach($user, ['local_role' => $role, 'is_active' => true]);

        return $user->fresh();
    }
}

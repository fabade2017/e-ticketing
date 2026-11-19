<?php

namespace Tests\Feature;

use App\Models\Hospital;
use App\Models\SuiteLink;
use App\Models\Ticket;
use App\Models\User;
use App\Modules\Maintenance\Models\Equipment;
use App\Modules\Maintenance\Models\MaintWorkOrder;
use App\Modules\Maintenance\Models\MaintWorkOrderPart;
use App\Modules\Supply\Models\SupplyGoodsIssued;
use App\Modules\Supply\Models\SupplyItem;
use App\Modules\Supply\Models\SupplyStockBalance;
use App\Shared\Integrations\SuiteIntegrationService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuiteIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_ticket_to_work_order_to_stock_issue_end_to_end(): void
    {
        $hospital = $this->hospital();
        $actor = $this->moduleUser($hospital);
        $equipment = Equipment::factory()->create(['hospital_id' => $hospital->id]);
        $item = SupplyItem::factory()->create();
        SupplyStockBalance::factory()->create([
            'item_id' => $item->id,
            'hospital_id' => $hospital->id,
            'quantity_on_hand' => 10,
        ]);
        $ticket = $this->ticket($actor);

        $integration = app(SuiteIntegrationService::class);
        $workOrder = $integration->openWorkOrderFromTicket($ticket, $actor, [
            'equipment_id' => $equipment->id,
        ]);
        $issue = $integration->issueStockForWorkOrder($workOrder, $actor, [
            'item_id' => $item->id,
            'quantity' => 3,
        ]);

        $this->assertSame($ticket->id, $workOrder->ticket_id);
        $this->assertSame('approved', $issue->status);
        $this->assertSame('work-order-'.$workOrder->id, $issue->idempotency_key);
        $this->assertSame(2, SuiteLink::count());
        $this->assertSame(1, MaintWorkOrderPart::count());
        $this->assertDatabaseHas('supply_stock_balances', [
            'item_id' => $item->id,
            'hospital_id' => $hospital->id,
            'quantity_on_hand' => 7,
        ]);
    }

    public function test_duplicate_stock_issue_for_work_order_does_not_double_decrement(): void
    {
        $hospital = $this->hospital();
        $actor = $this->moduleUser($hospital);
        $equipment = Equipment::factory()->create(['hospital_id' => $hospital->id]);
        $item = SupplyItem::factory()->create();
        SupplyStockBalance::factory()->create([
            'item_id' => $item->id,
            'hospital_id' => $hospital->id,
            'quantity_on_hand' => 10,
        ]);
        $ticket = $this->ticket($actor);

        $integration = app(SuiteIntegrationService::class);
        $workOrder = $integration->openWorkOrderFromTicket($ticket, $actor, ['equipment_id' => $equipment->id]);
        $attributes = ['item_id' => $item->id, 'quantity' => 3];

        $first = $integration->issueStockForWorkOrder($workOrder, $actor, $attributes);
        $second = $integration->issueStockForWorkOrder($workOrder, $actor, $attributes);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, SupplyGoodsIssued::count());
        $this->assertSame(1, MaintWorkOrderPart::count());
        $this->assertDatabaseHas('supply_stock_balances', [
            'item_id' => $item->id,
            'hospital_id' => $hospital->id,
            'quantity_on_hand' => 7,
        ]);
    }

    public function test_http_flow_can_open_work_order_from_ticket(): void
    {
        $hospital = $this->hospital();
        $actor = $this->moduleUser($hospital);
        $equipment = Equipment::factory()->create(['hospital_id' => $hospital->id]);
        $ticket = $this->ticket($actor);

        $response = $this->actingAs($actor)->post("/maintenance/tickets/{$ticket->id}/open", [
            'equipment_id' => $equipment->id,
        ]);

        $response->assertRedirect();
        $this->assertSame(1, MaintWorkOrder::where('ticket_id', $ticket->id)->count());
    }

    private function hospital(): Hospital
    {
        return Hospital::factory()->create();
    }

    private function moduleUser(Hospital $hospital): User
    {
        $user = User::factory()->create(['current_hospital_id' => $hospital->id]);
        $user->assignRole('MaintenanceTech');
        $user->assignRole('WarehouseManager');
        $hospital->users()->attach($user, ['local_role' => 'WarehouseManager', 'is_active' => true]);

        return $user->fresh();
    }

    private function ticket(User $actor): Ticket
    {
        return Ticket::create([
            'title' => 'Generator fault at motor pool',
            'description' => 'Generator fails to start after routine service.',
            'category' => 'equipment',
            'priority' => 'high',
            'status' => 'open',
            'user_id' => $actor->id,
        ]);
    }
}

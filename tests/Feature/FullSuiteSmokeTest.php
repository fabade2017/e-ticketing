<?php

namespace Tests\Feature;

use App\Models\Hospital;
use App\Models\SuiteLink;
use App\Models\Ticket;
use App\Models\User;
use App\Modules\Care\Models\CarePatient;
use App\Modules\Care\Models\CareRegistration;
use App\Modules\Care\Services\CareBillingService;
use App\Modules\Maintenance\Models\Equipment;
use App\Modules\Supply\Models\SupplyItem;
use App\Modules\Supply\Models\SupplyStockBalance;
use App\Shared\Integrations\SuiteIntegrationService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FullSuiteSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_all_modules_work_together_with_the_pluggable_payment_flow(): void
    {
        $hospital = Hospital::factory()->create();
        $technician = $this->userWithRoles($hospital, 'MaintenanceTech', 'WarehouseManager');
        $equipment = Equipment::factory()->create(['hospital_id' => $hospital->id]);
        $item = SupplyItem::factory()->create();
        SupplyStockBalance::factory()->create([
            'item_id' => $item->id,
            'hospital_id' => $hospital->id,
            'quantity_on_hand' => 10,
        ]);
        $ticket = Ticket::create([
            'title' => 'Pump failure at outpost',
            'description' => 'Water pump stopped during morning service.',
            'category' => 'logistics',
            'priority' => 'high',
            'status' => 'open',
            'user_id' => $technician->id,
        ]);

        $integration = app(SuiteIntegrationService::class);
        $workOrder = $integration->openWorkOrderFromTicket($ticket, $technician, ['equipment_id' => $equipment->id]);
        $issue = $integration->issueStockForWorkOrder($workOrder, $technician, [
            'item_id' => $item->id,
            'quantity' => 2,
        ]);

        $reception = $this->userWithRoles($hospital, 'Reception');
        $accounts = $this->userWithRoles($hospital, 'Accounts');
        $portalUser = $this->userWithRoles($hospital, 'Patient');
        $patient = CarePatient::factory()->linkedTo($portalUser)->create([
            'hospital_id' => $hospital->id,
            'category' => 'civilian',
        ]);
        $registration = CareRegistration::factory()->approved()->create([
            'patient_id' => $patient->id,
            'hospital_id' => $hospital->id,
        ]);
        $billing = app(CareBillingService::class);
        $invoice = $billing->createInvoice($registration, $reception);
        $payment = $billing->submitPayment($invoice, $portalUser, [
            'gateway' => 'manual_transfer',
            'amount' => 10000,
            'bank_reference' => 'TRF-FULL-SUITE',
        ]);
        $billing->verifyPayment($payment, $accounts, [
            'bank_reference' => 'TRF-FULL-SUITE',
            'credited' => true,
        ]);

        $superAdmin = User::where('email', 'superadmin@army.mil.ng')->first();

        $this->assertSame($ticket->id, $workOrder->ticket_id);
        $this->assertSame('approved', $issue->status);
        $this->assertDatabaseHas('supply_stock_balances', [
            'item_id' => $item->id,
            'hospital_id' => $hospital->id,
            'quantity_on_hand' => 8,
        ]);
        $this->assertSame('verified', $payment->fresh()->status);
        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame(2, SuiteLink::count());

        $this->actingAs($superAdmin)->get('/reports')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Reports/Index')
                ->where('selectedModule', 'all')
                ->where('suiteSections', fn ($sections) => collect($sections)->pluck('key')->contains('care')
                    && collect($sections)->pluck('key')->contains('maintenance')
                    && collect($sections)->pluck('key')->contains('supply')
                )
            );

        Sanctum::actingAs($technician);
        $this->getJson('/api/suite/modules')
            ->assertOk()
            ->assertJsonFragment(['key' => 'maintenance'])
            ->assertJsonFragment(['key' => 'supply']);
    }

    private function userWithRoles(Hospital $hospital, string ...$roles): User
    {
        $user = User::factory()->create(['current_hospital_id' => $hospital->id]);

        foreach ($roles as $role) {
            $user->assignRole($role);
        }

        $hospital->users()->attach($user, [
            'local_role' => $roles[0],
            'is_active' => true,
        ]);

        return $user->fresh();
    }
}

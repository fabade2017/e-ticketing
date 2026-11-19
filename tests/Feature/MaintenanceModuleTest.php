<?php

namespace Tests\Feature;

use App\Models\Hospital;
use App\Models\SuiteNotification;
use App\Models\User;
use App\Modules\Maintenance\Models\Equipment;
use App\Modules\Maintenance\Models\MaintOverdueFlag;
use App\Modules\Maintenance\Models\MaintPreventiveSchedule;
use App\Modules\Maintenance\Models\MaintWorkOrder;
use App\Modules\Maintenance\Services\WorkOrderService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_work_order_can_be_created_assigned_and_completed(): void
    {
        $hospital = $this->hospital();
        $actor = $this->moduleUser('MaintenanceTech', $hospital);
        $equipment = Equipment::factory()->create(['hospital_id' => $hospital->id]);
        $service = app(WorkOrderService::class);

        $workOrder = $service->create([
            'equipment_id' => $equipment->id,
            'title' => 'Replace defective pump',
            'priority' => 'high',
        ], $actor);

        $this->assertSame('pending', $workOrder->status);

        $service->assign($workOrder, $actor, $actor);
        $this->assertSame('assigned', $workOrder->fresh()->status);
        $this->assertSame($actor->id, $workOrder->fresh()->assigned_to);

        $service->complete($workOrder, $actor);
        $this->assertSame('completed', $workOrder->fresh()->status);
        $this->assertNotNull($workOrder->fresh()->completed_at);
        $this->assertSame(3, $workOrder->serviceLogs()->count());
    }

    public function test_preventive_command_generates_work_order_and_advances_schedule(): void
    {
        $hospital = $this->hospital();
        $equipment = Equipment::factory()->create(['hospital_id' => $hospital->id]);
        $schedule = MaintPreventiveSchedule::factory()->due()->create([
            'hospital_id' => $hospital->id,
            'equipment_id' => $equipment->id,
            'interval_days' => 30,
        ]);

        $this->artisan('maintenance:generate-preventive')->assertSuccessful();

        $workOrder = MaintWorkOrder::firstOrFail();

        $this->assertSame(1, MaintWorkOrder::count());
        $this->assertSame($equipment->id, $workOrder->equipment_id);
        $this->assertSame($workOrder->id, $schedule->fresh()->last_work_order_id);
        $this->assertTrue($schedule->fresh()->next_due_at->isFuture());
    }

    public function test_overdue_command_flags_work_order_and_notifies_technicians(): void
    {
        $hospital = $this->hospital();
        $this->moduleUser('MaintenanceTech', $hospital);
        $equipment = Equipment::factory()->create(['hospital_id' => $hospital->id]);
        $workOrder = MaintWorkOrder::factory()->overdue()->create([
            'hospital_id' => $hospital->id,
            'equipment_id' => $equipment->id,
        ]);

        $this->artisan('maintenance:flag-overdue')->assertSuccessful();

        $this->assertTrue(MaintOverdueFlag::where('work_order_id', $workOrder->id)->exists());
        $this->assertTrue(SuiteNotification::where('module', 'maintenance')
            ->where('type', 'maintenance.work_order_overdue')
            ->exists());
    }

    public function test_maintenance_index_renders_for_authorized_user(): void
    {
        $hospital = $this->hospital();
        $user = $this->moduleUser('MaintenanceTech', $hospital);

        $this->actingAs($user)->get('/maintenance')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Maintenance/Index'));
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

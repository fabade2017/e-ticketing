<?php

namespace Tests\Feature;

use App\Models\Hospital;
use App\Models\SuiteNotification;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuiteDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_dashboard_shares_suite_modules_hospital_and_insights(): void
    {
        $admin = User::where('email', 'admin@army.mil.ng')->first();

        $this->actingAs($admin)->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard')
                ->where('suite.current_hospital.code', 'LMH')
                ->where('suite.modules', fn ($modules) => collect($modules)->pluck('key')->contains('ticketing'))
                ->where('suite.rooms', fn ($rooms) => collect($rooms)->pluck('key')->all() === ['ticketing'])
                ->where('suite.rooms', fn ($rooms) => collect(data_get($rooms, '0.menus', []))->pluck('label')->contains('All Tickets'))
                ->where('suiteInsights.0.key', 'ticketing')
            );
    }

    public function test_soldier_dashboard_does_not_expose_other_module_links(): void
    {
        $soldier = User::where('email', 'soldier@army.mil.ng')->first();

        $this->actingAs($soldier)->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('suite.modules', fn ($modules) => ! collect($modules)->pluck('key')->contains('maintenance'))
                ->where('suite.modules', fn ($modules) => ! collect($modules)->pluck('key')->contains('care'))
                ->where('suite.modules', fn ($modules) => ! collect($modules)->pluck('key')->contains('care-portal'))
                ->where('suite.rooms', fn ($rooms) => collect($rooms)->pluck('key')->all() === ['ticketing'])
                ->where('suite.rooms', fn ($rooms) => ! collect(data_get($rooms, '0.menus', []))->pluck('label')->contains('All Tickets'))
            );
    }

    public function test_dashboard_shares_care_workspace_module_for_care_staff(): void
    {
        $reception = User::factory()->create([
            'current_hospital_id' => Hospital::first()->id,
        ]);
        $reception->assignRole('Reception');

        $this->actingAs($reception->fresh())->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('suite.modules', fn ($modules) => collect($modules)->pluck('key')->contains('care'))
                ->where('suite.modules', fn ($modules) => ! collect($modules)->pluck('key')->contains('care-portal'))
            );
    }

    public function test_dashboard_shares_patient_portal_module_for_portal_users(): void
    {
        $patient = User::factory()->create([
            'current_hospital_id' => Hospital::first()->id,
        ]);
        $patient->assignRole('Patient');

        $this->actingAs($patient->fresh())->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('suite.modules', fn ($modules) => collect($modules)->pluck('key')->contains('care-portal'))
                ->where('suite.modules', fn ($modules) => ! collect($modules)->pluck('key')->contains('care'))
            );
    }

    public function test_care_room_shows_workspace_or_portal_menus_based_on_role(): void
    {
        $hospital = Hospital::first();
        $reception = User::factory()->create(['current_hospital_id' => $hospital->id]);
        $reception->assignRole('Reception');
        $patient = User::factory()->create(['current_hospital_id' => $hospital->id]);
        $patient->assignRole('Patient');

        $this->actingAs($reception->fresh())->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('suite.rooms', fn ($rooms) => collect($rooms)->pluck('key')->contains('care'))
                ->where('suite.rooms', fn ($rooms) => collect(data_get(collect($rooms)->firstWhere('key', 'care'), 'menus', []))->pluck('label')->contains('Registrations'))
                ->where('suite.rooms', fn ($rooms) => ! collect(data_get(collect($rooms)->firstWhere('key', 'care'), 'menus', []))->pluck('label')->contains('Patient Dashboard'))
            );

        $this->actingAs($patient->fresh())->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('suite.rooms', fn ($rooms) => collect($rooms)->pluck('key')->contains('care'))
                ->where('suite.rooms', fn ($rooms) => collect(data_get(collect($rooms)->firstWhere('key', 'care'), 'menus', []))->pluck('label')->contains('Patient Dashboard'))
                ->where('suite.rooms', fn ($rooms) => ! collect(data_get(collect($rooms)->firstWhere('key', 'care'), 'menus', []))->pluck('label')->contains('Registrations'))
            );
    }

    public function test_maintenance_and_supply_rooms_are_exposed_to_their_roles(): void
    {
        $hospital = Hospital::first();
        $technician = User::factory()->create(['current_hospital_id' => $hospital->id]);
        $technician->assignRole('MaintenanceTech');
        $warehouse = User::factory()->create(['current_hospital_id' => $hospital->id]);
        $warehouse->assignRole('WarehouseManager');

        $this->actingAs($technician->fresh())->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('suite.rooms', fn ($rooms) => collect($rooms)->pluck('key')->contains('maintenance'))
                ->where('suite.rooms', fn ($rooms) => ! collect($rooms)->pluck('key')->contains('supply'))
            );

        $this->actingAs($warehouse->fresh())->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('suite.rooms', fn ($rooms) => collect($rooms)->pluck('key')->contains('supply'))
                ->where('suite.rooms', fn ($rooms) => ! collect($rooms)->pluck('key')->contains('maintenance'))
                ->where('suite.rooms', fn ($rooms) => collect(data_get(collect($rooms)->firstWhere('key', 'supply'), 'menus', []))->pluck('label')->contains('Stock Approvals'))
            );
    }

    public function test_soldier_dashboard_reports_conditional_status_counts(): void
    {
        $soldier = User::where('email', 'soldier@army.mil.ng')->first();

        foreach (['open', 'in_progress', 'resolved'] as $status) {
            Ticket::create([
                'title' => "Additional {$status} ticket",
                'description' => 'Dashboard aggregation regression coverage.',
                'category' => 'logistics',
                'priority' => 'normal',
                'status' => $status,
                'user_id' => $soldier->id,
            ]);
        }

        $this->actingAs($soldier)->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('stats.my_total', 5)
                ->where('stats.my_open', 3)
                ->where('stats.my_progress', 1)
                ->where('stats.my_resolved', 1)
            );
    }

    public function test_unread_suite_notifications_are_counted_and_can_be_marked_read(): void
    {
        $user = User::where('email', 'admin@army.mil.ng')->first();
        $notification = SuiteNotification::factory()->for($user, 'notifiable')->create();

        $this->assertSame(1, $user->suiteNotifications()->unread()->count());

        $notification->markRead();

        $this->assertSame(0, $user->fresh()->suiteNotifications()->unread()->count());
    }
}

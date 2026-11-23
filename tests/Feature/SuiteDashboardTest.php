<?php

namespace Tests\Feature;

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

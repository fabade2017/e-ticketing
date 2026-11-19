<?php

namespace Tests\Feature;

use App\Models\SuiteNotification;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiSuiteEndpointsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_suite_modules_requires_authentication(): void
    {
        $this->getJson('/api/suite/modules')
            ->assertUnauthorized();
    }

    public function test_soldier_only_gets_permitted_suite_modules(): void
    {
        $soldier = User::where('email', 'soldier@army.mil.ng')->first();
        Sanctum::actingAs($soldier);

        $response = $this->getJson('/api/suite/modules')->assertOk();
        $keys = collect($response->json('modules'))->pluck('key')->all();

        $this->assertContains('ticketing', $keys);
        $this->assertNotContains('care', $keys);
        $this->assertNotContains('maintenance', $keys);
    }

    public function test_suite_notifications_are_scoped_to_authenticated_user(): void
    {
        $admin = User::where('email', 'admin@army.mil.ng')->first();
        $soldier = User::where('email', 'soldier@army.mil.ng')->first();
        SuiteNotification::factory()->for($admin, 'notifiable')->create(['title' => 'Mine']);
        SuiteNotification::factory()->for($soldier, 'notifiable')->create(['title' => 'Other']);
        Sanctum::actingAs($admin);

        $this->getJson('/api/suite/notifications')
            ->assertOk()
            ->assertJsonFragment(['title' => 'Mine'])
            ->assertJsonMissing(['title' => 'Other']);
    }
}

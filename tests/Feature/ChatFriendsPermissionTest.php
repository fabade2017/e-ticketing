<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ChatFriendsPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_chat_and_friends_permissions_are_seeded_and_assigned(): void
    {
        $this->assertDatabaseHas('permissions', ['name' => 'use chat']);
        $this->assertDatabaseHas('permissions', ['name' => 'use friends']);

        $soldier = User::where('email', 'soldier@army.mil.ng')->first();
        $this->assertTrue($soldier->hasPermissionTo('use chat'));
        $this->assertTrue($soldier->hasPermissionTo('use friends'));
    }

    public function test_chat_and_friends_routes_require_permission(): void
    {
        $role = Role::create(['name' => 'Guest']);
        $guest = User::factory()->create(['is_active' => true]);
        $guest->assignRole($role);

        $this->actingAs($guest->fresh())->get('/chat')->assertForbidden();
        $this->actingAs($guest->fresh())->get('/friends')->assertForbidden();
        $this->actingAs($guest->fresh())->get('/chat/unread')->assertForbidden();
    }

    public function test_chat_and_friends_routes_allowed_with_permission(): void
    {
        $soldier = User::where('email', 'soldier@army.mil.ng')->first();

        $this->actingAs($soldier)->get('/chat')->assertOk();
        $this->actingAs($soldier)->get('/friends')->assertOk();
    }

    public function test_superadmin_always_has_access(): void
    {
        $sa = User::where('email', 'superadmin@army.mil.ng')->first();

        $this->assertTrue($sa->hasPermissionTo('use chat'));
        $this->assertTrue($sa->hasPermissionTo('use friends'));

        $this->actingAs($sa)->get('/chat')->assertOk();
        $this->actingAs($sa)->get('/friends')->assertOk();
    }
}

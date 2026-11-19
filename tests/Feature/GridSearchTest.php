<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GridSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_officers_grid_supports_search(): void
    {
        $admin = User::where('email', 'superadmin@army.mil.ng')->first();
        $match = User::factory()->create([
            'name' => 'Searchable Zulu',
            'service_number' => 'NA/ZZ/999',
            'rank' => 'Pte',
            'is_active' => true,
        ]);
        $match->assignRole('Soldier');
        User::factory()->create(['name' => 'Someone Else', 'service_number' => 'NA/XX/111']);

        $this->actingAs($admin)->get('/officers?search=Searchable')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Officers/Index')
                ->has('officers.data', 1)
                ->where('officers.data.0.name', 'Searchable Zulu')
                ->where('filters.search', 'Searchable')
            );
    }

    public function test_admin_users_grid_supports_search(): void
    {
        $admin = User::where('email', 'superadmin@army.mil.ng')->first();

        $this->actingAs($admin)->get('/admin/users?search=NA/SA/001')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Users')
                ->has('users.data', 1)
                ->where('users.data.0.service_number', 'NA/SA/001')
                ->where('filters.search', 'NA/SA/001')
            );
    }

    public function test_user_crud_grid_supports_search(): void
    {
        $admin = User::where('email', 'superadmin@army.mil.ng')->first();

        $this->actingAs($admin)->get('/admin/users/crud?search=Soldier')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/UsersCrud')
                ->has('users.data', 1)
                ->where('users.data.0.name', 'Soldier One')
                ->where('filters.search', 'Soldier')
            );
    }

    public function test_friends_grid_supports_search(): void
    {
        $soldier = User::where('email', 'soldier@army.mil.ng')->first();

        $this->actingAs($soldier)->get('/friends?search=Unit')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Friends/Index')
                ->has('users.data', 1)
                ->where('users.data.0.name', 'Unit Admin')
                ->where('filters.search', 'Unit')
            );
    }

    public function test_kb_grid_supports_search(): void
    {
        $soldier = User::where('email', 'soldier@army.mil.ng')->first();

        $this->actingAs($soldier)->get('/kb?search=nonexistent-term')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Kb/Index')
                ->has('articles.data', 0)
                ->where('filters.search', 'nonexistent-term')
            );
    }
}

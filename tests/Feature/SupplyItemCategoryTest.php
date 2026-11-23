<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Supply\Models\SupplyItemCategory;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplyItemCategoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_admin_can_open_category_manager_with_default_categories(): void
    {
        $admin = User::where('email', 'admin@army.mil.ng')->firstOrFail();

        $this->actingAs($admin)->get('/admin/supply-categories')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/SupplyCategories')
                ->where('categories', fn ($categories) => collect($categories)->pluck('name')->contains('medical'))
            );
    }

    public function test_superadmin_creates_category(): void
    {
        $superAdmin = User::where('email', 'superadmin@army.mil.ng')->firstOrFail();

        $this->actingAs($superAdmin)->post('/admin/supply-categories', [
            'name' => 'Surgical Consumables',
            'code' => 'SUR',
            'description' => 'Sterile supplies',
            'is_active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('supply_item_categories', [
            'name' => 'Surgical Consumables',
            'code' => 'SUR',
            'is_active' => true,
        ]);
    }

    public function test_duplicate_category_name_is_rejected(): void
    {
        $admin = User::where('email', 'admin@army.mil.ng')->firstOrFail();

        $this->actingAs($admin)->post('/admin/supply-categories', [
            'name' => 'medical',
            'code' => 'DUP',
            'is_active' => true,
        ])->assertSessionHasErrors('name');

        $this->assertSame(1, SupplyItemCategory::where('name', 'medical')->count());
    }

    public function test_admin_updates_category(): void
    {
        $admin = User::where('email', 'admin@army.mil.ng')->firstOrFail();
        $category = SupplyItemCategory::factory()->create(['name' => 'Old Name']);

        $this->actingAs($admin)->put("/admin/supply-categories/{$category->id}", [
            'name' => 'Renamed Category',
            'code' => 'REN',
            'description' => 'Updated description',
            'is_active' => false,
        ])->assertRedirect();

        $this->assertDatabaseHas('supply_item_categories', [
            'id' => $category->id,
            'name' => 'Renamed Category',
            'code' => 'REN',
            'is_active' => false,
        ]);
    }

    public function test_admin_deletes_category(): void
    {
        $admin = User::where('email', 'admin@army.mil.ng')->firstOrFail();
        $category = SupplyItemCategory::factory()->create();

        $this->actingAs($admin)->delete("/admin/supply-categories/{$category->id}")->assertRedirect();

        $this->assertModelMissing($category);
    }

    public function test_soldier_cannot_open_category_manager(): void
    {
        $soldier = User::where('email', 'soldier@army.mil.ng')->firstOrFail();

        $this->actingAs($soldier)->get('/admin/supply-categories')->assertForbidden();
    }

    public function test_soldier_cannot_create_category(): void
    {
        $soldier = User::where('email', 'soldier@army.mil.ng')->firstOrFail();

        $this->actingAs($soldier)->post('/admin/supply-categories', [
            'name' => 'Unauthorized Category',
            'is_active' => true,
        ])->assertForbidden();

        $this->assertDatabaseMissing('supply_item_categories', ['name' => 'Unauthorized Category']);
    }
}

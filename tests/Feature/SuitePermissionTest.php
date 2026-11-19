<?php

namespace Tests\Feature;

use App\Models\Hospital;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\SuiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SuitePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_suite_permissions_are_granted_to_module_roles(): void
    {
        $this->assertTrue(Role::findByName('Doctor')->hasPermissionTo('use care workspace'));
        $this->assertTrue(Role::findByName('MaintenanceTech')->hasPermissionTo('use maintenance'));
        $this->assertTrue(Role::findByName('StoresOfficer')->hasPermissionTo('use supply'));
        $this->assertFalse(Role::findByName('Soldier')->hasPermissionTo('use supply'));
    }

    public function test_reseeding_restores_suite_permissions_without_clearing_role_edits(): void
    {
        $role = Role::findByName('Doctor');
        $role->revokePermissionTo('use care workspace');
        $this->assertFalse($role->fresh()->hasPermissionTo('use care workspace'));

        $this->seed(SuiteSeeder::class);

        $this->assertTrue($role->fresh()->hasPermissionTo('use care workspace'));
    }

    public function test_staff_without_hospital_selection_receives_409_json(): void
    {
        Route::middleware(['web', 'auth', 'hospital'])->get('/_test/hospital', fn () => response()->json(['ok' => true]));

        $user = User::factory()->create(['current_hospital_id' => null]);
        $user->assignRole('Doctor');

        $this->actingAs($user)->getJson('/_test/hospital')->assertStatus(409);
    }

    public function test_staff_with_assigned_hospital_can_continue(): void
    {
        Route::middleware(['web', 'auth', 'hospital'])->get('/_test/hospital', fn () => response()->json(['ok' => true]));

        $hospital = Hospital::factory()->create();
        $user = User::factory()->create(['current_hospital_id' => $hospital->id]);
        $user->assignRole('Doctor');
        $hospital->users()->attach($user, ['local_role' => 'Doctor', 'is_active' => true]);

        $this->actingAs($user)->getJson('/_test/hospital')
            ->assertOk()
            ->assertJson(['ok' => true]);
    }
}

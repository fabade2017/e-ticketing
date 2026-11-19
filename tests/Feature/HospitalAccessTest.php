<?php

namespace Tests\Feature;

use App\Models\Hospital;
use App\Models\User;
use App\Shared\Identity\HospitalAccess;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HospitalAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_user_can_only_access_assigned_active_hospitals(): void
    {
        $user = User::factory()->create();
        $assigned = Hospital::factory()->create();
        $inactiveAssignment = Hospital::factory()->create();
        $inactiveHospital = Hospital::factory()->create(['is_active' => false]);

        $assigned->users()->attach($user, ['local_role' => 'Doctor', 'is_active' => true]);
        $inactiveAssignment->users()->attach($user, ['local_role' => 'Doctor', 'is_active' => false]);
        $inactiveHospital->users()->attach($user, ['local_role' => 'Doctor', 'is_active' => true]);

        $hospitalAccess = new HospitalAccess;

        $this->assertTrue($user->canAccessHospital($assigned->id));
        $this->assertFalse($user->canAccessHospital($inactiveAssignment->id));
        $this->assertFalse($user->canAccessHospital($inactiveHospital->id));
        $this->assertSame([$assigned->id], $hospitalAccess->hospitalsFor($user)->pluck('id')->all());
    }

    public function test_superadmin_can_access_every_hospital(): void
    {
        $user = User::factory()->create();
        $user->assignRole('SuperAdmin');
        $hospital = Hospital::factory()->create();

        $this->assertTrue($user->fresh()->canAccessHospital($hospital->id));
        $this->assertTrue((new HospitalAccess)->hospitalsFor($user->fresh())->contains($hospital));
    }
}

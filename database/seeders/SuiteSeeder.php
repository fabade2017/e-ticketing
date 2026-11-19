<?php

namespace Database\Seeders;

use App\Models\Hospital;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SuiteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'use ticketing',
            'use care portal',
            'use care workspace',
            'use maintenance',
            'approve work orders',
            'use supply',
            'receive stock',
            'issue stock',
            'approve stock adjustments',
            'view network reports',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $rolePermissions = [
            'SuperAdmin' => $permissions,
            'Admin' => ['use ticketing'],
            'Support' => ['use ticketing'],
            'Soldier' => ['use ticketing'],
            'Patient' => ['use care portal'],
            'Reception' => ['use care workspace'],
            'Accounts' => ['use care workspace'],
            'Nurse' => ['use care workspace'],
            'Doctor' => ['use care workspace'],
            'Lab' => ['use care workspace'],
            'Pharmacy' => ['use care workspace'],
            'RecordsOfficer' => ['use care workspace'],
            'HospitalAdmin' => ['use care workspace', 'view reports'],
            'NetworkAdmin' => ['use care workspace', 'view reports', 'view network reports'],
            'MaintenanceTech' => ['use maintenance'],
            'WorkshopSupervisor' => ['use maintenance', 'approve work orders'],
            'LogisticsAdmin' => ['use maintenance', 'approve work orders', 'view reports'],
            'StoresOfficer' => ['use supply', 'receive stock'],
            'WarehouseManager' => ['use supply', 'receive stock', 'issue stock', 'approve stock adjustments'],
            'SupplyAdmin' => ['use supply', 'receive stock', 'issue stock', 'approve stock adjustments', 'view reports'],
        ];

        foreach ($rolePermissions as $roleName => $names) {
            Role::firstOrCreate(['name' => $roleName])->givePermissionTo($names);
        }

        $lagos = Hospital::firstOrCreate(['code' => 'LMH'], [
            'name' => 'Lagos Military Hospital',
            'state' => 'Lagos',
            'is_active' => true,
        ]);

        $abuja = Hospital::firstOrCreate(['code' => 'AMH'], [
            'name' => 'Abuja Military Hospital',
            'state' => 'FCT',
            'is_active' => true,
        ]);

        foreach (User::all() as $user) {
            $localRole = $user->getRoleNames()->first() ?? 'Soldier';

            $lagos->users()->syncWithoutDetaching([
                $user->id => ['local_role' => $localRole, 'is_active' => true],
            ]);

            if ($user->hasRole('SuperAdmin')) {
                $abuja->users()->syncWithoutDetaching([
                    $user->id => ['local_role' => 'SuperAdmin', 'is_active' => true],
                ]);
            }

            if ($user->current_hospital_id === null) {
                $user->update(['current_hospital_id' => $lagos->id]);
            }
        }
    }
}

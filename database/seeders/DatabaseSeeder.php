<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $roles = ['SuperAdmin', 'Admin', 'Support', 'Soldier'];
        $roleModels = [];
        foreach ($roles as $r) {
            $roleModels[$r] = Role::firstOrCreate(['name' => $r]);
        }

        $perms = [
            'manage tickets', 'manage users', 'toggle face mode', 'view reports',
            'view proposal coverage', 'view documentation', 'view help', 'view about', 'view knowledge base',
            'use chat', 'use friends',
        ];
        foreach ($perms as $p) {
            Permission::firstOrCreate(['name' => $p]);
        }

        // SuperAdmin always holds every permission.
        $roleModels['SuperAdmin']->givePermissionTo($perms);

        // Other roles are only granted on first creation so that SuperAdmin
        // edits made in Roles & Permissions persist across re-seeds/deploys.
        $rolePermissions = [
            'Admin' => ['manage tickets', 'manage users', 'view reports', 'view proposal coverage', 'view documentation', 'view help', 'view about', 'view knowledge base', 'use chat', 'use friends'],
            'Support' => ['manage tickets', 'view reports', 'view proposal coverage', 'view documentation', 'view help', 'view about', 'view knowledge base', 'use chat', 'use friends'],
            'Soldier' => ['view proposal coverage', 'view documentation', 'view help', 'view about', 'view knowledge base', 'use chat', 'use friends'],
        ];
        foreach ($rolePermissions as $roleName => $permList) {
            if ($roleModels[$roleName]->wasRecentlyCreated) {
                $roleModels[$roleName]->givePermissionTo($permList);
            }
        }

        // SuperAdmin - can toggle Local/Cloud
        $sa = User::firstOrCreate(['email' => 'superadmin@army.mil.ng'], [
            'name' => 'Super Admin',
            'service_number' => 'NA/SA/001',
            'rank' => 'Brig Gen',
            'unit' => 'HQ',
            'password' => Hash::make('Admin@123'),
            'is_active' => true,
        ]);
        $sa->assignRole('SuperAdmin');

        // Admin
        $admin = User::firstOrCreate(['email' => 'admin@army.mil.ng'], [
            'name' => 'Unit Admin',
            'service_number' => 'NA/ADM/002',
            'rank' => 'Col',
            'unit' => 'Sector 2',
            'password' => Hash::make('Admin@123'),
            'is_active' => true,
        ]);
        $admin->assignRole('Admin');

        // Soldier
        $soldier = User::firstOrCreate(['email' => 'soldier@army.mil.ng'], [
            'name' => 'Soldier One',
            'service_number' => 'NA/SL/003',
            'rank' => 'Pte',
            'unit' => 'Border Outpost A',
            'password' => Hash::make('Soldier@123'),
            'is_active' => true,
        ]);
        $soldier->assignRole('Soldier');

        SystemSetting::firstOrCreate(['key' => 'face_auth_mode'], ['value' => config('face_auth.mode', 'local'), 'description' => 'local or cloud']);
        SystemSetting::firstOrCreate(['key' => 'face_auth_enabled'], ['value' => '1', 'description' => 'enable/disable face auth']);
        SystemSetting::firstOrCreate(['key' => 'face_cloud_provider'], ['value' => 'aws', 'description' => 'aws or azure']);

        // Sample tickets
        if (Ticket::count() === 0) {
            Ticket::create(['title' => 'Water supply disruption at outpost', 'description' => 'Borehole pump faulty, requires logistics support', 'category' => 'logistics', 'priority' => 'high', 'status' => 'open', 'user_id' => $soldier->id, 'location' => 'Border Outpost A', 'unit' => 'Border Outpost A']);
            Ticket::create(['title' => 'Medical evacuation request', 'description' => 'Personnel requires urgent medical attention', 'category' => 'medical', 'priority' => 'urgent', 'status' => 'open', 'user_id' => $soldier->id, 'location' => 'Sector 2', 'unit' => 'Border Outpost A']);
        }
    }
}

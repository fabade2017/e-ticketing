<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Make Chat and Friends assignable RBAC permissions.
     * Existing users keep access by granting to the core roles once.
     */
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guard = config('auth.defaults.guard', 'web');
        $permissions = ['use chat', 'use friends'];

        foreach ($permissions as $name) {
            Permission::findOrCreate($name, $guard);
        }

        foreach (['SuperAdmin', 'Admin', 'Support', 'Soldier'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', $guard)->first();
            $role?->givePermissionTo($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guard = config('auth.defaults.guard', 'web');

        foreach (['SuperAdmin', 'Admin', 'Support', 'Soldier'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', $guard)->first();
            $role?->revokePermissionTo(['use chat', 'use friends']);
        }

        foreach (['use chat', 'use friends'] as $name) {
            Permission::where('name', $name)->where('guard_name', $guard)->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};

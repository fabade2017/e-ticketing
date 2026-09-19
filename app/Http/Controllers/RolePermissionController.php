<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionController extends Controller
{
    public function index()
    {
        if (!auth()->user()->hasRole('SuperAdmin')) abort(403);
        return Inertia::render('Admin/Roles', [
            'roles' => Role::with('permissions')->get(),
            'permissions' => Permission::pluck('name'),
            'users' => \App\Models\User::with('roles','permissions')->get(['id','name','email','service_number']),
        ]);
    }

    public function storeRole(Request $request)
    {
        if (!auth()->user()->hasRole('SuperAdmin')) abort(403);
        $request->validate(['name' => 'required|string|unique:roles,name', 'permissions' => 'array', 'permissions.*' => 'exists:permissions,name']);
        $role = Role::create(['name' => $request->name]);
        if ($request->permissions) $role->syncPermissions($request->permissions);
        return back()->with('success', "Role {$role->name} created");
    }

    public function updateRole(Request $request, Role $role)
    {
        if (!auth()->user()->hasRole('SuperAdmin')) abort(403);
        $request->validate(['permissions' => 'array', 'permissions.*' => 'exists:permissions,name']);
        $role->syncPermissions($request->permissions ?? []);
        return back()->with('success', "Role {$role->name} permissions updated");
    }

    public function destroyRole(Role $role)
    {
        if (!auth()->user()->hasRole('SuperAdmin')) abort(403);
        if (in_array($role->name, ['SuperAdmin','Admin','Support','Soldier'])) return back()->withErrors(['name' => 'Cannot delete core roles']);
        $role->delete();
        return back()->with('success', "Role deleted");
    }

    public function storePermission(Request $request)
    {
        if (!auth()->user()->hasRole('SuperAdmin')) abort(403);
        $request->validate(['name' => 'required|string|unique:permissions,name']);
        Permission::create(['name' => $request->name]);
        return back()->with('success', "Permission {$request->name} created");
    }

    public function assignRole(Request $request)
    {
        if (!auth()->user()->hasRole('SuperAdmin')) abort(403);
        $request->validate(['user_id' => 'required|exists:users,id', 'roles' => 'required|array', 'roles.*' => 'exists:roles,name']);
        $user = \App\Models\User::findOrFail($request->user_id);
        $user->syncRoles($request->roles);
        return back()->with('success', "Roles updated for {$user->name}");
    }

    public function assignPermission(Request $request)
    {
        if (!auth()->user()->hasRole('SuperAdmin')) abort(403);
        $request->validate(['user_id' => 'required|exists:users,id', 'permissions' => 'array', 'permissions.*' => 'exists:permissions,name']);
        $user = \App\Models\User::findOrFail($request->user_id);
        $user->syncPermissions($request->permissions ?? []);
        return back()->with('success', "Direct permissions updated for {$user->name}");
    }
}

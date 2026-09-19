<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class DepartmentController extends Controller
{
    public function index()
    {
        if (!auth()->user()->hasRole('SuperAdmin')) abort(403);
        return Inertia::render('Admin/Departments', [
            'departments' => Department::withCount('users')->get(),
            'users' => User::with(['roles','department'])->get(['id','name','service_number','rank','department_id']),
            'roles' => Role::pluck('name'),
        ]);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasRole('SuperAdmin')) abort(403);
        $request->validate(['name'=>'required|string|unique:departments,name','code'=>'nullable|string|unique:departments,code','description'=>'nullable|string']);
        Department::create($request->only('name','code','description'));
        return back()->with('success','Department created');
    }

    public function update(Request $request, Department $department)
    {
        if (!auth()->user()->hasRole('SuperAdmin')) abort(403);
        $request->validate(['name'=>'required|string','description'=>'nullable|string']);
        $department->update($request->only('name','description'));
        return back()->with('success','Department updated');
    }

    public function destroy(Department $department)
    {
        if (!auth()->user()->hasRole('SuperAdmin')) abort(403);
        $department->delete();
        return back()->with('success','Department deleted');
    }

    public function assignUser(Request $request)
    {
        if (!auth()->user()->hasRole('SuperAdmin')) abort(403);
        $request->validate(['user_id'=>'required|exists:users,id','department_id'=>'required|exists:departments,id','role'=>'nullable|exists:roles,name']);
        $user = User::findOrFail($request->user_id);
        $user->update(['department_id'=>$request->department_id]);
        if ($request->role) $user->syncRoles([$request->role]);
        return back()->with('success',"{$user->name} assigned to department & role");
    }

    public function searchUsers(Request $request)
    {
        $q = User::with(['roles','department']);
        if ($request->role) $q->whereHas('roles', fn($qq)=>$qq->where('name',$request->role));
        if ($request->department_id) $q->where('department_id',$request->department_id);
        if ($request->search) $q->where(fn($qq)=>$qq->where('name','like',"%{$request->search}%")->orWhere('service_number','like',"%{$request->search}%"));
        return response()->json($q->limit(20)->get(['id','name','service_number','rank','department_id']));
    }
}

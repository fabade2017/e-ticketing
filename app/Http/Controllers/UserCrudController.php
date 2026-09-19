<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserCrudController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->hasRole('SuperAdmin')) abort(403);
        $q = User::with(['roles','department']);
        if ($request->search) $q->where(fn($qq)=>$qq->where('name','like',"%{$request->search}%")->orWhere('service_number','like',"%{$request->search}%")->orWhere('email','like',"%{$request->search}%"));
        if ($request->role) $q->whereHas('roles', fn($qq)=>$qq->where('name',$request->role));
        if ($request->department_id) $q->where('department_id',$request->department_id);
        return Inertia::render('Admin/UsersCrud', [
            'users'=> $q->latest()->paginate(20)->withQueryString(),
            'roles'=> Role::pluck('name'),
            'departments'=> Department::pluck('name','id'),
            'filters'=> $request->only(['search','role','department_id']),
        ]);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasRole('SuperAdmin')) abort(403);
        $request->validate([
            'name'=>'required|string|max:100',
            'email'=>'required|email|unique:users,email',
            'service_number'=>'required|string|unique:users,service_number',
            'rank'=>'nullable|string',
            'unit'=>'nullable|string',
            'department_id'=>'nullable|exists:departments,id',
            'phone'=>'nullable|string',
            'role'=>'required|exists:roles,name',
            'password'=>'nullable|string|min:8',
        ]);
        $user = User::create([
            'name'=>$request->name,'email'=>$request->email,'service_number'=>$request->service_number,
            'rank'=>$request->rank,'unit'=>$request->unit,'department_id'=>$request->department_id,
            'phone'=>$request->phone,'password'=>Hash::make($request->password ?: 'Password@123'),'is_active'=>true,
        ]);
        $user->assignRole($request->role);
        return back()->with('success','User created');
    }

    public function update(Request $request, User $user)
    {
        if (!auth()->user()->hasRole('SuperAdmin')) abort(403);
        $request->validate([
            'name'=>'required|string','email'=>'required|email|unique:users,email,'.$user->id,
            'service_number'=>'required|string|unique:users,service_number,'.$user->id,
            'rank'=>'nullable|string','unit'=>'nullable|string','department_id'=>'nullable|exists:departments,id',
            'phone'=>'nullable|string','role'=>'required|exists:roles,name','is_active'=>'boolean',
        ]);
        $user->update($request->only('name','email','service_number','rank','unit','department_id','phone','is_active'));
        $user->syncRoles([$request->role]);
        if ($request->filled('password')) $user->update(['password'=>Hash::make($request->password)]);
        return back()->with('success','User updated');
    }

    public function destroy(User $user)
    {
        if (!auth()->user()->hasRole('SuperAdmin')) abort(403);
        if ($user->id === auth()->id()) return back()->withErrors(['user'=>'Cannot delete yourself']);
        $user->delete();
        return back()->with('success','User deleted');
    }
}

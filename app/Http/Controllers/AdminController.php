<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use App\Models\User;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class AdminController extends Controller
{
    public function dashboard()
    {
        $user = auth()->user();
        $isSoldier = $user->hasRole('Soldier') && !$user->hasAnyRole(['SuperAdmin','Admin','Support']);
        if ($isSoldier) {
            $my = \App\Models\Ticket::where('user_id', $user->id);
            return Inertia::render('Dashboard', [
                'stats' => [
                    'my_total' => (clone $my)->count(),
                    'my_open' => (clone $my)->where('status','open')->count(),
                    'my_progress' => (clone $my)->where('status','in_progress')->count(),
                    'my_resolved' => (clone $my)->where('status','resolved')->count(),
                    'isSoldier' => true,
                ],
                'ai' => null,
                'recent' => \App\Models\Ticket::where('user_id',$user->id)->latest()->limit(5)->get(),
            ]);
        }
        $ai = \App\Services\AiInsightsService::generate();
        return Inertia::render('Dashboard', [
            'stats' => array_merge([
                'open' => \App\Models\Ticket::where('status', 'open')->count(),
                'progress' => \App\Models\Ticket::where('status', 'in_progress')->count(),
                'resolved' => \App\Models\Ticket::where('status', 'resolved')->count(),
                'closed' => \App\Models\Ticket::where('status', 'closed')->count(),
                'total' => \App\Models\Ticket::count(),
                'users' => User::count(),
                'face_enrolled' => User::whereNotNull('face_embedding')->count(),
                'isSoldier' => false,
            ], $ai['stats']),
            'ai' => $ai,
            'recent' => \App\Models\Ticket::with('user')->latest()->limit(5)->get(),
        ]);
    }

    public function users()
    {
        if (!auth()->user()->hasAnyRole(['SuperAdmin', 'Admin'])) abort(403);
        return Inertia::render('Admin/Users', [
            'users' => User::with('roles')->latest()->paginate(20),
            'roles' => Role::pluck('name'),
        ]);
    }

    public function settings()
    {
        if (!auth()->user()->hasRole('SuperAdmin')) abort(403);
        return Inertia::render('Admin/Settings', [
            'settings' => [
                'face_auth_mode' => SystemSetting::getValue('face_auth_mode', config('face_auth.mode', 'local')),
                'face_auth_enabled' => SystemSetting::getValue('face_auth_enabled', '1') === '1',
                'face_cloud_provider' => SystemSetting::getValue('face_cloud_provider', 'aws'),
                'threshold' => config('face_auth.threshold'),
            ],
        ]);
    }

    public function updateUserRole(Request $request, User $user)
    {
        if (!auth()->user()->hasRole('SuperAdmin')) abort(403);
        $request->validate(['role' => 'required|exists:roles,name']);
        $user->syncRoles([$request->role]);
        return back()->with('success', "Role updated to {$request->role}");
    }
}

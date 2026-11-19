<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use App\Models\Ticket;
use App\Models\User;
use App\Services\AiInsightsService;
use App\Shared\Reporting\SuiteInsights;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class AdminController extends Controller
{
    public function dashboard(Request $request, SuiteInsights $suiteInsights)
    {
        $user = auth()->user();
        $isSoldier = $user->hasRole('Soldier') && ! $user->hasAnyRole(['SuperAdmin', 'Admin', 'Support']);
        if ($isSoldier) {
            $my = Ticket::where('user_id', $user->id);

            return Inertia::render('Dashboard', [
                'stats' => [
                    'my_total' => (clone $my)->count(),
                    'my_open' => (clone $my)->where('status', 'open')->count(),
                    'my_progress' => (clone $my)->where('status', 'in_progress')->count(),
                    'my_resolved' => (clone $my)->where('status', 'resolved')->count(),
                    'isSoldier' => true,
                ],
                'ai' => null,
                'recent' => Ticket::where('user_id', $user->id)->latest()->limit(5)->get(),
                'scope' => 'personal',
                'canSeeAll' => false,
                'suiteInsights' => $suiteInsights->for($user, false),
            ]);
        }

        // RBAC-aware dashboard: SuperAdmin sees all, others see scoped view by default
        $canSeeAll = $user->hasAnyRole(['SuperAdmin', 'Admin', 'Support']);
        $scope = $request->input('scope', 'scoped');
        // soldier cannot use scope=all, privileged can opt-in via ?scope=all
        $isAll = $scope === 'all' && $canSeeAll;
        // special case: SuperAdmin's scoped == all, keep consistent flag
        if ($user->hasRole('SuperAdmin') && $scope !== 'all') {
            // SuperAdmin defaults to all but can still use scoped if explicitly asked
            $isAll = $scope === 'all' || $scope === 'scoped' ? false : $isAll;
            // Actually SuperAdmin scoped vs all distinction: default all for backwards compat
            if ($scope === 'scoped' && ! $request->has('scope')) {
                // default for SuperAdmin remains 'all'
                $isAll = false;
            }
            // Normalize: if SuperAdmin with no param => show all
            if (! $request->has('scope')) {
                $isAll = true;
            }
        }

        // Build scoped base query
        $base = Ticket::query();
        if (! $isAll) {
            $base->visibleTo($user);
        }

        // Pass user-scoped AI (reports honour same visibility)
        $ai = AiInsightsService::generate(['user' => $isAll ? null : $user, 'from' => $request->input('from'), 'to' => $request->input('to')]);

        // Stats limited to visible scope
        $scopedOpen = (clone $base)->where('status', 'open')->count();
        $scopedProgress = (clone $base)->where('status', 'in_progress')->count();
        $scopedResolved = (clone $base)->where('status', 'resolved')->count();
        $scopedClosed = (clone $base)->where('status', 'closed')->count();
        $scopedTotal = (clone $base)->count();

        // Users/face stats: scoped for Admin/Support to department/unit, global for SuperAdmin/all-view
        if ($isAll || $user->hasRole('SuperAdmin')) {
            $usersCount = User::count();
            $faceEnrolled = User::whereNotNull('face_embedding')->count();
        } else {
            $uQ = User::query();
            if ($user->department_id) {
                $uQ->where('department_id', $user->department_id);
            } elseif ($user->unit) {
                $uQ->where('unit', $user->unit);
            } else {
                $uQ->where('id', $user->id);
            }
            $usersCount = (clone $uQ)->count();
            $faceEnrolled = (clone $uQ)->whereNotNull('face_embedding')->count();
        }

        $recent = (clone $base)->with('user')->latest()->limit(5)->get();

        $scopeLabel = $isAll ? 'all' : ($user->hasRole('SuperAdmin') ? 'scoped' : 'personal');
        // For Admin/Support, label as department/unit/personal depending
        if (! $isAll && ! $user->hasRole('SuperAdmin')) {
            if ($user->department_id) {
                $scopeLabel = 'department';
            } elseif ($user->unit) {
                $scopeLabel = 'unit';
            } else {
                $scopeLabel = 'personal';
            }
        }

        return Inertia::render('Dashboard', [
            'stats' => array_merge([
                'open' => $scopedOpen,
                'progress' => $scopedProgress,
                'resolved' => $scopedResolved,
                'closed' => $scopedClosed,
                'total' => $scopedTotal,
                'users' => $usersCount,
                'face_enrolled' => $faceEnrolled,
                'isSoldier' => false,
                'scope' => $scopeLabel,
                'isAll' => $isAll,
            ], $ai['stats']),
            'ai' => $ai,
            'recent' => $recent,
            'scope' => $scopeLabel,
            'canSeeAll' => $canSeeAll,
            'isAll' => $isAll,
            'suiteInsights' => $suiteInsights->for($user, $isAll),
        ]);
    }

    public function users(Request $request)
    {
        if (! auth()->user()->hasAnyRole(['SuperAdmin', 'Admin'])) {
            abort(403);
        }
        $search = trim((string) $request->input('search'));

        $users = User::with('roles:id,name')
            ->select(['id', 'name', 'email', 'service_number', 'unit', 'department_id', 'face_enrolled_at', 'is_active', 'created_at'])
            ->when($search !== '', fn ($q) => $q->where(fn ($qq) => $qq
                ->where('name', 'like', "%{$search}%")
                ->orWhere('service_number', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Users', [
            'users' => $users,
            'roles' => fn () => Role::pluck('name'),
            'filters' => $request->only('search'),
        ]);
    }

    public function settings()
    {
        if (! auth()->user()->hasRole('SuperAdmin')) {
            abort(403);
        }

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
        if (! auth()->user()->hasRole('SuperAdmin')) {
            abort(403);
        }
        $request->validate(['role' => 'required|exists:roles,name']);
        $user->syncRoles([$request->role]);

        return back()->with('success', "Role updated to {$request->role}");
    }
}

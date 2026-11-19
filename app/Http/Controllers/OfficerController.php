<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class OfficerController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (! $user->hasAnyRole(['SuperAdmin', 'Admin', 'Support'])) {
            abort(403, 'Officers directory is restricted to Admin/Support/SuperAdmin');
        }
        $isAll = $request->input('scope') === 'all' && $user->hasAnyRole(['SuperAdmin', 'Admin']);
        if ($user->hasRole('SuperAdmin') && ! $request->has('scope')) {
            $isAll = true;
        }

        $search = trim((string) $request->input('search'));

        $q = User::with('roles:id,name')
            ->select(['id', 'name', 'email', 'service_number', 'rank', 'unit', 'department_id', 'face_enrolled_at', 'is_active', 'created_at'])
            ->latest();
        // Default: scoped to department/unit for Admin/Support unless scope=all
        if (! $isAll && ! $user->hasRole('SuperAdmin')) {
            if ($user->department_id) {
                $q->where('department_id', $user->department_id);
            } elseif ($user->unit) {
                $q->where('unit', $user->unit);
            }
        }
        if ($search !== '') {
            $q->where(fn ($qq) => $qq->where('name', 'like', "%{$search}%")->orWhere('service_number', 'like', "%{$search}%")->orWhere('rank', 'like', "%{$search}%"));
        }
        if ($request->filled('role')) {
            $q->whereHas('roles', fn ($qq) => $qq->where('name', $request->input('role')));
        }
        if ($request->input('face') === 'enrolled') {
            $q->whereNotNull('face_enrolled_at');
        }
        if ($request->input('face') === 'pending') {
            $q->whereNull('face_enrolled_at');
        }

        // Stats scoped similarly
        $statsQ = User::query();
        if (! $isAll && ! $user->hasRole('SuperAdmin')) {
            if ($user->department_id) {
                $statsQ->where('department_id', $user->department_id);
            } elseif ($user->unit) {
                $statsQ->where('unit', $user->unit);
            }
        }
        $scopeLabel = $isAll ? 'all' : ($user->hasRole('SuperAdmin') ? 'all' : ($user->department_id ? 'department' : ($user->unit ? 'unit' : 'personal')));

        return Inertia::render('Officers/Index', [
            'officers' => $q->paginate(24)->withQueryString(),
            'filters' => $request->only(['search', 'role', 'face', 'scope']),
            'roles' => fn () => Role::pluck('name'),
            'stats' => fn () => [
                'total' => (clone $statsQ)->count(),
                'enrolled' => (clone $statsQ)->whereNotNull('face_enrolled_at')->count(),
                'byRank' => (clone $statsQ)->selectRaw('rank, count(*) as c')->groupBy('rank')->pluck('c', 'rank'),
            ],
            'scope' => $scopeLabel,
            'isAll' => $isAll,
            'canSeeAll' => $user->hasAnyRole(['SuperAdmin', 'Admin']),
        ]);
    }
}

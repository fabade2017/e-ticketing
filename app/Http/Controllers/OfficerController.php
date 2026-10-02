<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Illuminate\Http\Request;

class OfficerController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user->hasAnyRole(['SuperAdmin','Admin','Support'])) abort(403, 'Officers directory is restricted to Admin/Support/SuperAdmin');
        $isAll = $request->input('scope') === 'all' && $user->hasAnyRole(['SuperAdmin','Admin']);
        if ($user->hasRole('SuperAdmin') && !$request->has('scope')) $isAll = true;

        $q = User::with(['roles'])->latest();
        // Default: scoped to department/unit for Admin/Support unless scope=all
        if (!$isAll && !$user->hasRole('SuperAdmin')) {
            if ($user->department_id) $q->where('department_id', $user->department_id);
            elseif ($user->unit) $q->where('unit', $user->unit);
        }
        if ($request->search) $q->where(fn($qq)=>$qq->where('name','like',"%{$request->search}%")->orWhere('service_number','like',"%{$request->search}%")->orWhere('rank','like',"%{$request->search}%"));
        if ($request->role) $q->whereHas('roles', fn($qq)=>$qq->where('name',$request->role));
        if ($request->face === 'enrolled') $q->whereNotNull('face_embedding');
        if ($request->face === 'pending') $q->whereNull('face_embedding');

        // Stats scoped similarly
        $statsQ = User::query();
        if (!$isAll && !$user->hasRole('SuperAdmin')) {
            if ($user->department_id) $statsQ->where('department_id', $user->department_id);
            elseif ($user->unit) $statsQ->where('unit', $user->unit);
        }
        $scopeLabel = $isAll ? 'all' : ($user->hasRole('SuperAdmin') ? 'all' : ($user->department_id ? 'department' : ($user->unit ? 'unit' : 'personal')));

        return Inertia::render('Officers/Index', [
            'officers' => $q->paginate(24)->withQueryString(),
            'filters' => $request->only(['search','role','face','scope']),
            'roles' => \Spatie\Permission\Models\Role::pluck('name'),
            'stats' => [
                'total' => (clone $statsQ)->count(),
                'enrolled' => (clone $statsQ)->whereNotNull('face_embedding')->count(),
                'byRank' => (clone $statsQ)->selectRaw('rank, count(*) as c')->groupBy('rank')->pluck('c','rank'),
            ],
            'scope' => $scopeLabel,
            'isAll' => $isAll,
            'canSeeAll' => $user->hasAnyRole(['SuperAdmin','Admin']),
        ]);
    }
}

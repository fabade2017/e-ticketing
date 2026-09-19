<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Illuminate\Http\Request;

class OfficerController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['SuperAdmin','Admin','Support'])) abort(403, 'Officers directory is restricted to Admin/Support/SuperAdmin');
        $q = User::with(['roles'])->latest();
        if ($request->search) $q->where(fn($qq)=>$qq->where('name','like',"%{$request->search}%")->orWhere('service_number','like',"%{$request->search}%")->orWhere('rank','like',"%{$request->search}%"));
        if ($request->role) $q->whereHas('roles', fn($qq)=>$qq->where('name',$request->role));
        if ($request->face === 'enrolled') $q->whereNotNull('face_embedding');
        if ($request->face === 'pending') $q->whereNull('face_embedding');

        return Inertia::render('Officers/Index', [
            'officers' => $q->paginate(24)->withQueryString(),
            'filters' => $request->only(['search','role','face']),
            'roles' => \Spatie\Permission\Models\Role::pluck('name'),
            'stats' => [
                'total' => User::count(),
                'enrolled' => User::whereNotNull('face_embedding')->count(),
                'byRank' => User::selectRaw('rank, count(*) as c')->groupBy('rank')->pluck('c','rank'),
            ],
        ]);
    }
}

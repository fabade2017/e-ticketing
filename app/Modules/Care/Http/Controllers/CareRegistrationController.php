<?php

namespace App\Modules\Care\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Care\Models\CarePatientSponsor;
use App\Modules\Care\Models\CareRegistration;
use App\Modules\Care\Services\CareRegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CareRegistrationController extends Controller
{
    public function index(Request $request): Response
    {
        $hospitalId = $this->hospitalId($request);

        return Inertia::render('Care/Workspace/Registrations', [
            'registrations' => CareRegistration::with(['patient:id,first_name,last_name,network_number,category', 'sponsor'])
                ->when($hospitalId !== null, fn ($query) => $query->where('hospital_id', $hospitalId))
                ->when($request->input('status'), fn ($query, string $status) => $query->where('status', $status))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'filters' => $request->only(['status', 'scope']),
            'canSeeAll' => auth()->user()->hasRole('SuperAdmin') || auth()->user()->can('view network reports'),
        ]);
    }

    public function review(Request $request, CareRegistration $registration, CareRegistrationService $registrations): RedirectResponse
    {
        Gate::authorize('reviewRegistration', $registration);

        $validated = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'identity_verified' => ['nullable', 'boolean'],
            'evidence_verified' => ['nullable', 'boolean'],
            'sponsor_approved' => ['nullable', 'boolean'],
        ]);

        $registrations->review($registration, auth()->user(), $validated['decision'], $validated);

        return back()->with('success', 'Registration reviewed.');
    }

    public function approveSponsor(CarePatientSponsor $sponsor, CareRegistrationService $registrations): RedirectResponse
    {
        $registrations->approveSponsor($sponsor, auth()->user());

        return back()->with('success', 'Sponsor approved.');
    }

    private function hospitalId(Request $request): ?int
    {
        $user = auth()->user();

        if ($user->hasRole('SuperAdmin') || ($request->input('scope') === 'all' && $user->can('view network reports'))) {
            return null;
        }

        return $user->current_hospital_id;
    }
}

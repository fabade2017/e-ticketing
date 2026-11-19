<?php

namespace App\Modules\Care\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Hospital;
use App\Modules\Care\Models\CarePatient;
use App\Modules\Care\Models\CareReferral;
use App\Modules\Care\Models\CareVisit;
use App\Modules\Care\Services\CareClinicalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CareReferralController extends Controller
{
    public function index(Request $request): Response
    {
        $user = auth()->user();
        $hospitalId = $user->hasRole('SuperAdmin') || ($request->input('scope') === 'all' && $user->can('view network reports'))
            ? null
            : $user->current_hospital_id;

        return Inertia::render('Care/Workspace/Referrals', [
            'referrals' => CareReferral::with(['patient:id,first_name,last_name,network_number', 'toHospital:id,name,code', 'fromHospital:id,name,code'])
                ->when($hospitalId !== null, fn ($query) => $query->where(fn ($withinHospital) => $withinHospital
                    ->where('from_hospital_id', $hospitalId)
                    ->orWhere('to_hospital_id', $hospitalId)))
                ->when($request->input('status'), fn ($query, string $status) => $query->where('status', $status))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'hospitals' => Hospital::active()->get(['id', 'name', 'code']),
            'filters' => $request->only(['status', 'scope']),
        ]);
    }

    public function store(Request $request, CareClinicalService $clinical): RedirectResponse
    {
        $validated = $request->validate([
            'patient_id' => ['required', 'integer', 'exists:care_patients,id'],
            'to_hospital_id' => ['required', 'integer', 'exists:hospitals,id'],
            'visit_id' => ['nullable', 'integer', 'exists:care_visits,id'],
            'reason' => ['required', 'string'],
        ]);

        $patient = CarePatient::findOrFail($validated['patient_id']);
        $receivingHospital = Hospital::findOrFail($validated['to_hospital_id']);
        $visit = $validated['visit_id'] ? CareVisit::findOrFail($validated['visit_id']) : null;

        Gate::authorize('viewPatient', $patient);
        $clinical->refer($patient, $receivingHospital, auth()->user(), $validated['reason'], $visit);

        return back()->with('success', 'Referral request sent.');
    }

    public function accept(CareReferral $referral, CareClinicalService $clinical): RedirectResponse
    {
        $clinical->acceptReferral($referral, auth()->user());

        return back()->with('success', 'Referral accepted.');
    }
}

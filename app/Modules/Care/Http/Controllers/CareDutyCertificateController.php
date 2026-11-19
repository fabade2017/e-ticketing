<?php

namespace App\Modules\Care\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Care\Models\CareDutyCertificate;
use App\Modules\Care\Models\CarePatient;
use App\Modules\Care\Services\CareDutyCertificateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CareDutyCertificateController extends Controller
{
    public function index(Request $request, CareDutyCertificateService $certificates): Response
    {
        $user = auth()->user();
        $hospitalId = $user->hasRole('SuperAdmin') || ($request->input('scope') === 'all' && $user->can('view network reports'))
            ? null
            : $user->current_hospital_id;

        $patients = CarePatient::active()
            ->when($hospitalId !== null, fn ($query) => $query->where('hospital_id', $hospitalId))
            ->with(['dutyCertificates' => fn ($query) => $query->current()->latest('issued_at')->limit(1)])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $statuses = $patients->getCollection()
            ->mapWithKeys(fn (CarePatient $patient): array => [$patient->id => $certificates->statusFor($patient)])
            ->all();

        return Inertia::render('Care/Workspace/DutyCertificates', [
            'patients' => $patients,
            'statuses' => $statuses,
            'filters' => $request->only(['scope']),
        ]);
    }

    public function store(Request $request, CareDutyCertificateService $certificates): RedirectResponse
    {
        $validated = $request->validate([
            'patient_id' => ['required', 'integer', 'exists:care_patients,id'],
            'visit_id' => ['nullable', 'integer', 'exists:care_visits,id'],
            'status' => ['required', 'in:'.implode(',', CareDutyCertificateService::STATUSES)],
            'statement' => ['required', 'string'],
            'review_date' => ['nullable', 'date'],
        ]);

        $patient = CarePatient::findOrFail($validated['patient_id']);
        Gate::authorize('issueDutyCertificate', $patient);

        $certificates->issue($patient, auth()->user(), $validated);

        return back()->with('success', 'Duty certificate issued.');
    }

    public function administrative(Request $request, CareDutyCertificate $certificate, CareDutyCertificateService $certificates): RedirectResponse
    {
        Gate::authorize('maintainDutyRecord', $certificate);

        $validated = $request->validate([
            'administrative_note' => ['nullable', 'string'],
        ]);

        $certificates->updateAdministrative($certificate, auth()->user(), $validated);

        return back()->with('success', 'Administrative note updated.');
    }
}

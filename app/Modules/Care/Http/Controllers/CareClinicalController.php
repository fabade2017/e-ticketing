<?php

namespace App\Modules\Care\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Hospital;
use App\Modules\Care\Models\Bed;
use App\Modules\Care\Models\CareAdmission;
use App\Modules\Care\Models\CareLabOrder;
use App\Modules\Care\Models\CareLabResult;
use App\Modules\Care\Models\CarePatient;
use App\Modules\Care\Models\CareVisit;
use App\Modules\Care\Services\CareClinicalService;
use App\Modules\Care\Services\CareDutyCertificateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CareClinicalController extends Controller
{
    public function visits(Request $request): Response
    {
        $hospitalId = $this->hospitalId($request);

        return Inertia::render('Care/Workspace/Visits', [
            'visits' => CareVisit::with('patient:id,first_name,last_name,network_number')
                ->when($hospitalId !== null, fn ($query) => $query->where('hospital_id', $hospitalId))
                ->when($request->input('status'), fn ($query, string $status) => $query->where('status', $status))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'filters' => $request->only(['status', 'scope']),
        ]);
    }

    public function storeVisit(Request $request, CareClinicalService $clinical): RedirectResponse
    {
        $validated = $request->validate([
            'patient_id' => ['required', 'integer', 'exists:care_patients,id'],
            'appointment_id' => ['nullable', 'integer', 'exists:care_appointments,id'],
            'registration_id' => ['nullable', 'integer', 'exists:care_registrations,id'],
            'visit_type' => ['required', 'string', 'max:50'],
        ]);

        $patient = CarePatient::findOrFail($validated['patient_id']);
        Gate::authorize('viewPatient', $patient);

        $visit = $clinical->startVisit($patient, auth()->user(), $validated);

        return redirect()->route('care.visits.show', $visit)->with('success', 'Visit opened.');
    }

    public function showVisit(CareVisit $visit, CareDutyCertificateService $certificates): Response
    {
        Gate::authorize('viewClinicalHistory', $visit->patient);

        $visit->load([
            'patient',
            'clinician:id,name',
            'vitals',
            'diagnoses',
            'labOrders.result.images',
            'prescriptions',
        ]);

        return Inertia::render('Care/Workspace/VisitShow', [
            'visit' => $visit,
            'dutyStatus' => $certificates->statusFor($visit->patient),
        ]);
    }

    public function completeVisit(CareVisit $visit, CareClinicalService $clinical): RedirectResponse
    {
        Gate::authorize('viewClinicalHistory', $visit->patient);
        $clinical->completeVisit($visit, auth()->user());

        return back()->with('success', 'Visit completed.');
    }

    public function storeSummary(Request $request, CareVisit $visit, CareClinicalService $clinical): RedirectResponse
    {
        $validated = $request->validate([
            'published_summary' => ['required', 'string'],
        ]);

        Gate::authorize('viewClinicalHistory', $visit->patient);
        $clinical->publishSummary($visit, auth()->user(), $validated['published_summary']);

        return back()->with('success', 'Consultation summary published to the patient portal.');
    }

    public function storeVitals(Request $request, CareVisit $visit, CareClinicalService $clinical): RedirectResponse
    {
        $validated = $request->validate([
            'height_cm' => ['nullable', 'numeric', 'min:0'],
            'weight_kg' => ['nullable', 'numeric', 'min:0'],
            'temperature_c' => ['nullable', 'numeric'],
            'pulse' => ['nullable', 'integer', 'min:0'],
            'bp_systolic' => ['nullable', 'integer', 'min:0'],
            'bp_diastolic' => ['nullable', 'integer', 'min:0'],
            'respiratory_rate' => ['nullable', 'integer', 'min:0'],
            'oxygen_saturation' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        Gate::authorize('viewClinicalHistory', $visit->patient);
        $clinical->recordVitals($visit, auth()->user(), $validated);

        return back()->with('success', 'Vitals recorded.');
    }

    public function storeDiagnosis(Request $request, CareVisit $visit, CareClinicalService $clinical): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:50'],
            'description' => ['required', 'string', 'max:500'],
            'type' => ['nullable', 'in:provisional,final'],
        ]);

        Gate::authorize('viewClinicalHistory', $visit->patient);
        $clinical->addDiagnosis($visit, $visit->patient, auth()->user(), $validated);

        return back()->with('success', 'Diagnosis recorded.');
    }

    public function orderLab(Request $request, CareVisit $visit, CareClinicalService $clinical): RedirectResponse
    {
        $validated = $request->validate([
            'test_code' => ['nullable', 'string', 'max:50'],
            'test_name' => ['required', 'string', 'max:150'],
            'priority' => ['nullable', 'in:routine,urgent,stat'],
        ]);

        Gate::authorize('viewClinicalHistory', $visit->patient);
        $clinical->orderLab($visit, $visit->patient, auth()->user(), $validated);

        return back()->with('success', 'Lab order created.');
    }

    public function lab(Request $request): Response
    {
        $hospitalId = $this->hospitalId($request);

        return Inertia::render('Care/Workspace/Lab', [
            'orders' => CareLabOrder::with(['patient:id,first_name,last_name,network_number', 'result.images'])
                ->when($hospitalId !== null, fn ($query) => $query->where('hospital_id', $hospitalId))
                ->when($request->input('status'), fn ($query, string $status) => $query->where('status', $status))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'filters' => $request->only(['status', 'scope']),
        ]);
    }

    public function storeLabResult(Request $request, CareLabOrder $order, CareClinicalService $clinical): RedirectResponse
    {
        $validated = $request->validate([
            'findings' => ['nullable', 'string'],
        ]);

        $clinical->createLabResult($order, auth()->user(), $validated);

        return back()->with('success', 'Lab result draft created.');
    }

    public function publishLabResult(Request $request, CareLabResult $result, CareClinicalService $clinical): RedirectResponse
    {
        $validated = $request->validate([
            'findings' => ['required', 'string'],
        ]);

        $clinical->publishLabResult($result, auth()->user(), $validated['findings']);

        return back()->with('success', 'Lab result published.');
    }

    public function uploadImage(Request $request, CareLabResult $result, CareClinicalService $clinical): RedirectResponse
    {
        $validated = $request->validate([
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:5120'],
        ]);

        $clinical->attachLabImage($result, auth()->user(), $validated['image']);

        return back()->with('success', 'Lab image uploaded.');
    }

    public function prescribe(Request $request, CareVisit $visit, CareClinicalService $clinical): RedirectResponse
    {
        $validated = $request->validate([
            'medication' => ['required', 'string', 'max:150'],
            'dosage' => ['nullable', 'string', 'max:100'],
            'frequency' => ['nullable', 'string', 'max:100'],
            'duration' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        Gate::authorize('viewClinicalHistory', $visit->patient);
        $clinical->prescribe($visit, $visit->patient, auth()->user(), $validated);

        return back()->with('success', 'Prescription created.');
    }

    public function admissions(Request $request): Response
    {
        $hospitalId = $this->hospitalId($request);

        return Inertia::render('Care/Workspace/Admissions', [
            'admissions' => CareAdmission::with(['patient:id,first_name,last_name,network_number', 'ward', 'bed'])
                ->when($hospitalId !== null, fn ($query) => $query->where('hospital_id', $hospitalId))
                ->when($request->input('status'), fn ($query, string $status) => $query->where('status', $status))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'beds' => Bed::whereIn('hospital_id', $hospitalId === null ? Hospital::active()->pluck('id') : [$hospitalId])
                ->where('status', 'available')
                ->with('ward:id,name,code')
                ->get(['id', 'hospital_id', 'ward_id', 'code']),
            'filters' => $request->only(['status', 'scope']),
        ]);
    }

    public function admit(Request $request, CareClinicalService $clinical): RedirectResponse
    {
        $validated = $request->validate([
            'patient_id' => ['required', 'integer', 'exists:care_patients,id'],
            'bed_id' => ['required', 'integer', 'exists:beds,id'],
            'diagnosis' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string'],
        ]);

        $patient = CarePatient::findOrFail($validated['patient_id']);
        $bed = Bed::findOrFail($validated['bed_id']);

        $clinical->admit($patient, $bed, auth()->user(), $validated);

        return back()->with('success', 'Patient admitted.');
    }

    public function discharge(CareAdmission $admission, CareClinicalService $clinical): RedirectResponse
    {
        $clinical->discharge($admission, auth()->user());

        return back()->with('success', 'Patient checked out.');
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

<?php

namespace App\Modules\Care\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Hospital;
use App\Models\SystemSetting;
use App\Models\User;
use App\Modules\Care\Models\CareLabResult;
use App\Modules\Care\Models\CarePatient;
use App\Modules\Care\Services\CareAppointmentService;
use App\Modules\Care\Services\CareDutyCertificateService;
use App\Modules\Care\Services\CareRegistrationService;
use App\Services\PaymentGatewayManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CarePortalController extends Controller
{
    public function dashboard(Request $request, CareDutyCertificateService $certificates): Response
    {
        $patient = $this->resolvePatient($request);
        $canViewClinical = $patient->linked_user_id === auth()->id();

        $visits = $canViewClinical
            ? $patient->visits()->published()->with('clinician:id,name')->latest()->limit(10)->get()
            : collect();

        return Inertia::render('Care/Portal/Dashboard', [
            'patient' => $patient,
            'registrations' => $patient->registrations()->latest()->limit(10)->get(),
            'appointments' => $patient->appointments()->with('hospital:id,name,code')->latest('requested_for')->limit(10)->get(),
            'invoices' => $patient->invoices()->latest()->limit(10)->get(),
            'visits' => $visits,
            'dutyStatus' => $certificates->statusFor($patient),
            'canViewClinical' => $canViewClinical,
        ]);
    }

    public function registrations(Request $request): Response
    {
        $patient = $this->resolvePatient($request);

        return Inertia::render('Care/Portal/Registrations', [
            'patient' => $patient,
            'registrations' => $patient->registrations()->latest()->get(),
        ]);
    }

    public function storeRegistration(Request $request, CareRegistrationService $registrations): RedirectResponse
    {
        $validated = $request->validate([
            'patient_id' => ['sometimes', 'integer', 'exists:care_patients,id'],
            'category' => ['required', Rule::in(SystemSetting::options('care_registration_categories'))],
            'sponsor_id' => ['nullable', 'integer', 'exists:care_patient_sponsors,id'],
            'relationship' => ['nullable', Rule::in(SystemSetting::options('care_registration_relationships'))],
        ]);

        $patient = $this->resolvePatient($request);
        $registrations->create($patient, auth()->user(), $validated);

        return back()->with('success', 'Registration submitted for review.');
    }

    public function appointments(Request $request): Response
    {
        $patient = $this->resolvePatient($request);

        return Inertia::render('Care/Portal/Appointments', [
            'patient' => $patient,
            'appointments' => $patient->appointments()->with('hospital:id,name,code')->latest('requested_for')->get(),
        ]);
    }

    public function storeAppointment(Request $request, CareAppointmentService $appointments): RedirectResponse
    {
        $validated = $request->validate([
            'patient_id' => ['sometimes', 'integer', 'exists:care_patients,id'],
            'requested_for' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $patient = $this->resolvePatient($request);
        $appointments->request($patient, auth()->user(), $validated);

        return back()->with('success', 'Appointment request submitted. It is not confirmed until Reception confirms.');
    }

    public function invoices(Request $request, PaymentGatewayManager $gateways): Response
    {
        $patient = $this->resolvePatient($request);

        return Inertia::render('Care/Portal/Invoices', [
            'patient' => $patient,
            'invoices' => $patient->invoices()->with('payments')->latest()->get(),
            'paymentGateways' => $gateways->options(),
            'defaultGateway' => $gateways->defaultKey(),
        ]);
    }

    public function results(Request $request): Response
    {
        $patient = $this->resolvePatient($request);

        if ($patient->linked_user_id !== auth()->id()) {
            abort(403, 'Sponsors cannot view clinical results.');
        }

        return Inertia::render('Care/Portal/Results', [
            'patient' => $patient,
            'visits' => $patient->visits()->published()->with('clinician:id,name')->latest()->get(),
            'results' => CareLabResult::published()
                ->where('patient_id', $patient->id)
                ->with('labOrder:id,test_name,completed_at')
                ->latest()
                ->get(),
        ]);
    }

    private function resolvePatient(Request $request): CarePatient
    {
        $user = auth()->user();
        $patientId = $request->integer('patient_id');

        $patient = $patientId > 0
            ? CarePatient::findOrFail($patientId)
            : ($user->carePatient ?? $this->createLinkedPatient($user));

        Gate::authorize('viewPatient', $patient);

        return $patient;
    }

    private function createLinkedPatient(User $user): CarePatient
    {
        $hospitalId = $user->current_hospital_id
            ? Hospital::query()->whereKey($user->current_hospital_id)->where('is_active', true)->value('id')
            : null;

        $hospitalId ??= Hospital::query()->where('is_active', true)->value('id');

        if ($hospitalId === null) {
            abort(422, 'No active hospital is available for patient registration.');
        }

        $names = preg_split('/\s+/', trim((string) $user->name), 2) ?: [];

        return CarePatient::create([
            'network_number' => 'NET-'.Str::upper(Str::random(10)),
            'army_number' => $user->service_number,
            'category' => $user->hasRole('Soldier') ? 'military' : 'civilian',
            'hospital_id' => $hospitalId,
            'linked_user_id' => $user->id,
            'first_name' => $names[0] ?? 'Patient',
            'last_name' => $names[1] ?? ($user->service_number ?: 'Patient'),
            'phone' => $user->phone,
            'is_active' => true,
        ]);
    }
}

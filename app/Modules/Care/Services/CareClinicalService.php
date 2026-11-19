<?php

namespace App\Modules\Care\Services;

use App\Models\AuditTrail;
use App\Models\Hospital;
use App\Models\SuiteNotification;
use App\Models\User;
use App\Modules\Care\Models\Bed;
use App\Modules\Care\Models\CareAdmission;
use App\Modules\Care\Models\CareAppointment;
use App\Modules\Care\Models\CareDiagnosis;
use App\Modules\Care\Models\CareLabImage;
use App\Modules\Care\Models\CareLabOrder;
use App\Modules\Care\Models\CareLabResult;
use App\Modules\Care\Models\CarePatient;
use App\Modules\Care\Models\CarePrescription;
use App\Modules\Care\Models\CareReferral;
use App\Modules\Care\Models\CareVisit;
use App\Modules\Care\Models\CareVital;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CareClinicalService
{
    public function __construct(public CareAppointmentService $appointments) {}

    public function startVisit(CarePatient $patient, User $actor, array $attributes = []): CareVisit
    {
        $this->authorizeWorkspace($actor, $patient->hospital_id);

        $appointment = null;
        if (! empty($attributes['appointment_id'])) {
            $appointment = CareAppointment::findOrFail((int) $attributes['appointment_id']);

            if ($appointment->patient_id !== $patient->id || $appointment->hospital_id !== $patient->hospital_id) {
                throw ValidationException::withMessages(['appointment_id' => 'The selected appointment does not belong to this patient.']);
            }
        }

        $visit = CareVisit::create([
            'visit_number' => 'VIS-'.strtoupper(Str::random(8)),
            'hospital_id' => $patient->hospital_id,
            'patient_id' => $patient->id,
            'registration_id' => $attributes['registration_id'] ?? null,
            'appointment_id' => $appointment?->id,
            'visit_type' => $attributes['visit_type'] ?? 'consultation',
            'status' => 'open',
            'clinician_id' => $actor->id,
            'checked_in_at' => now(),
        ]);

        if ($appointment !== null) {
            $this->appointments->complete($appointment, $actor);
        }

        AuditTrail::record('care.visit_opened', $visit, [], $visit->hospital_id);

        return $visit;
    }

    public function completeVisit(CareVisit $visit, User $actor): CareVisit
    {
        $this->authorizeWorkspace($actor, $visit->hospital_id);

        $visit->forceFill([
            'status' => 'completed',
            'completed_at' => $visit->completed_at ?? now(),
        ])->save();

        AuditTrail::record('care.visit_completed', $visit, [], $visit->hospital_id);

        return $visit;
    }

    public function publishSummary(CareVisit $visit, User $actor, string $summary): CareVisit
    {
        $this->authorizePermission($actor, 'publish consultation summaries', $visit->hospital_id);

        if (trim($summary) === '') {
            throw ValidationException::withMessages(['published_summary' => 'A consultation summary is required.']);
        }

        $visit->forceFill([
            'published_summary' => $summary,
            'clinician_id' => $visit->clinician_id ?? $actor->id,
        ])->save();

        AuditTrail::record('care.consultation_summary_published', $visit, [], $visit->hospital_id);
        $this->notifyPatient($visit->patient, 'Your consultation summary is available', '/portal/results');

        return $visit;
    }

    public function recordVitals(CareVisit $visit, User $actor, array $attributes): CareVital
    {
        $this->authorizeWorkspace($actor, $visit->hospital_id);

        $vital = CareVital::create(array_merge([
            'visit_id' => $visit->id,
            'hospital_id' => $visit->hospital_id,
            'recorded_by' => $actor->id,
            'recorded_at' => now(),
        ], $attributes));

        AuditTrail::record('care.vitals_recorded', $vital, ['visit_id' => $visit->id], $visit->hospital_id);

        return $vital;
    }

    public function addDiagnosis(CareVisit $visit, CarePatient $patient, User $actor, array $attributes): CareDiagnosis
    {
        $this->authorizeWorkspace($actor, $visit->hospital_id);

        if (empty($attributes['description'])) {
            throw ValidationException::withMessages(['description' => 'A diagnosis description is required.']);
        }

        $diagnosis = CareDiagnosis::create(array_merge([
            'visit_id' => $visit->id,
            'hospital_id' => $visit->hospital_id,
            'patient_id' => $patient->id,
            'diagnosed_by' => $actor->id,
            'type' => $attributes['type'] ?? 'final',
            'is_active' => true,
        ], $attributes));

        AuditTrail::record('care.diagnosis_recorded', $diagnosis, ['visit_id' => $visit->id], $visit->hospital_id);

        return $diagnosis;
    }

    public function orderLab(CareVisit $visit, CarePatient $patient, User $actor, array $attributes): CareLabOrder
    {
        $this->authorizeWorkspace($actor, $visit->hospital_id);

        if (empty($attributes['test_name'])) {
            throw ValidationException::withMessages(['test_name' => 'A test name is required.']);
        }

        $order = CareLabOrder::create(array_merge([
            'order_number' => 'LAB-'.strtoupper(Str::random(8)),
            'visit_id' => $visit->id,
            'hospital_id' => $visit->hospital_id,
            'patient_id' => $patient->id,
            'ordered_by' => $actor->id,
            'status' => 'ordered',
        ], $attributes));

        AuditTrail::record('care.lab_ordered', $order, ['visit_id' => $visit->id], $visit->hospital_id);

        return $order;
    }

    public function createLabResult(CareLabOrder $order, User $actor, array $attributes = []): CareLabResult
    {
        $this->authorizePermission($actor, 'publish lab results', $order->hospital_id);

        return CareLabResult::create([
            'lab_order_id' => $order->id,
            'hospital_id' => $order->hospital_id,
            'patient_id' => $order->patient_id,
            'result_by' => $actor->id,
            'findings' => $attributes['findings'] ?? null,
            'status' => 'draft',
        ]);
    }

    public function publishLabResult(CareLabResult $result, User $actor, string $findings): CareLabResult
    {
        $this->authorizePermission($actor, 'publish lab results', $result->hospital_id);

        if (trim($findings) === '') {
            throw ValidationException::withMessages(['findings' => 'Written findings are required.']);
        }

        $result->forceFill([
            'findings' => $findings,
            'status' => 'published',
            'published_by' => $actor->id,
            'published_at' => now(),
        ])->save();

        $result->labOrder()->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        AuditTrail::record('care.lab_result_published', $result, [], $result->hospital_id);
        $this->notifyPatient($result->patient, 'Your lab result is available', '/portal/results');

        return $result;
    }

    public function attachLabImage(CareLabResult $result, User $actor, UploadedFile $file): CareLabImage
    {
        $this->authorizePermission($actor, 'upload lab images', $result->hospital_id);

        if ($result->images()->count() >= 3) {
            throw ValidationException::withMessages(['images' => 'A lab result can have at most three images.']);
        }

        if (! in_array($file->getMimeType(), ['image/jpeg', 'image/png'], true) || $file->getSize() > 5 * 1024 * 1024) {
            throw ValidationException::withMessages(['image' => 'Lab images must be JPG or PNG files of at most 5 MB.']);
        }

        $path = Storage::disk('public')->putFile('care/lab-images', $file);

        $image = CareLabImage::create([
            'lab_result_id' => $result->id,
            'hospital_id' => $result->hospital_id,
            'uploaded_by' => $actor->id,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        AuditTrail::record('care.lab_image_uploaded', $image, ['lab_result_id' => $result->id], $result->hospital_id);

        return $image;
    }

    public function prescribe(CareVisit $visit, CarePatient $patient, User $actor, array $attributes): CarePrescription
    {
        $this->authorizeWorkspace($actor, $visit->hospital_id);

        if (empty($attributes['medication'])) {
            throw ValidationException::withMessages(['medication' => 'A medication is required.']);
        }

        $prescription = CarePrescription::create(array_merge([
            'visit_id' => $visit->id,
            'hospital_id' => $visit->hospital_id,
            'patient_id' => $patient->id,
            'prescribed_by' => $actor->id,
            'status' => 'active',
        ], $attributes));

        AuditTrail::record('care.prescription_created', $prescription, ['visit_id' => $visit->id], $visit->hospital_id);

        return $prescription;
    }

    public function admit(CarePatient $patient, Bed $bed, User $actor, array $attributes = []): CareAdmission
    {
        $this->authorizePermission($actor, 'manage admissions', $patient->hospital_id);

        if ($bed->hospital_id !== $patient->hospital_id) {
            throw ValidationException::withMessages(['bed_id' => 'The selected bed belongs to another hospital.']);
        }

        return DB::transaction(function () use ($patient, $bed, $actor, $attributes): CareAdmission {
            $lockedBed = Bed::whereKey($bed->id)->lockForUpdate()->firstOrFail();

            if ($lockedBed->status !== 'available') {
                throw ValidationException::withMessages(['bed_id' => 'The selected bed is not available.']);
            }

            $admission = CareAdmission::create([
                'admission_number' => 'ADM-'.strtoupper(Str::random(8)),
                'hospital_id' => $patient->hospital_id,
                'patient_id' => $patient->id,
                'ward_id' => $lockedBed->ward_id,
                'bed_id' => $lockedBed->id,
                'admitted_by' => $actor->id,
                'status' => 'admitted',
                'diagnosis' => $attributes['diagnosis'] ?? null,
                'notes' => $attributes['notes'] ?? null,
                'admitted_at' => now(),
            ]);

            $lockedBed->forceFill([
                'status' => 'occupied',
                'current_patient_id' => $patient->id,
            ])->save();

            AuditTrail::record('care.patient_admitted', $admission, ['bed_id' => $lockedBed->id], $patient->hospital_id);

            return $admission;
        });
    }

    public function discharge(CareAdmission $admission, User $actor): CareAdmission
    {
        $this->authorizePermission($actor, 'manage admissions', $admission->hospital_id);

        if ($admission->status !== 'admitted') {
            throw ValidationException::withMessages(['status' => 'Only active admissions can be checked out.']);
        }

        return DB::transaction(function () use ($admission, $actor): CareAdmission {
            $admission->forceFill([
                'status' => 'discharged',
                'discharged_by' => $actor->id,
                'discharged_at' => now(),
            ])->save();

            $admission->bed->forceFill([
                'status' => 'available',
                'current_patient_id' => null,
            ])->save();

            AuditTrail::record('care.patient_checked_out', $admission, ['bed_id' => $admission->bed_id], $admission->hospital_id);

            return $admission;
        });
    }

    public function refer(CarePatient $patient, Hospital $receivingHospital, User $actor, string $reason, ?CareVisit $visit = null): CareReferral
    {
        $this->authorizePermission($actor, 'manage referrals', $patient->hospital_id);

        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'A referral reason is required.']);
        }

        $referral = CareReferral::create([
            'referral_number' => 'REF-'.strtoupper(Str::random(8)),
            'from_hospital_id' => $patient->hospital_id,
            'to_hospital_id' => $receivingHospital->id,
            'patient_id' => $patient->id,
            'visit_id' => $visit?->id,
            'requested_by' => $actor->id,
            'reason' => $reason,
            'status' => 'pending',
        ]);

        AuditTrail::record('care.referral_requested', $referral, [], $patient->hospital_id);
        $this->notifyHospitalRoles($receivingHospital->id, ['Doctor', 'HospitalAdmin'], 'A care referral requires acceptance', '/care/referrals');

        $previousVisit = $patient->visits()
            ->completed()
            ->whereNotNull('clinician_id')
            ->latest('completed_at')
            ->first();

        if ($previousVisit !== null) {
            SuiteNotification::create([
                'notifiable_type' => User::class,
                'notifiable_id' => $previousVisit->clinician_id,
                'module' => 'care',
                'type' => 'care.referral_previous_doctor',
                'title' => 'A patient you treated was referred',
                'url' => '/care/referrals',
                'hospital_id' => $patient->hospital_id,
            ]);
        }

        return $referral;
    }

    public function acceptReferral(CareReferral $referral, User $actor): CareReferral
    {
        $this->authorizePermission($actor, 'manage referrals', $referral->to_hospital_id);

        if ($referral->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'Only pending referrals can be accepted.']);
        }

        $referral->forceFill([
            'status' => 'accepted',
            'accepted_by' => $actor->id,
            'accepted_at' => now(),
        ])->save();

        AuditTrail::record('care.referral_accepted', $referral, [], $referral->to_hospital_id);

        return $referral;
    }

    private function authorizeWorkspace(User $actor, int $hospitalId): void
    {
        $this->authorizePermission($actor, 'use care workspace', $hospitalId);
    }

    private function authorizePermission(User $actor, string $permission, int $hospitalId): void
    {
        if (! $actor->can($permission)) {
            throw new AuthorizationException('You cannot perform this care action.');
        }

        if (! $actor->canAccessHospital($hospitalId)) {
            throw new AuthorizationException('You cannot act on this hospital.');
        }
    }

    private function notifyPatient(CarePatient $patient, string $title, string $url): void
    {
        if ($patient->linked_user_id === null) {
            return;
        }

        SuiteNotification::create([
            'notifiable_type' => User::class,
            'notifiable_id' => $patient->linked_user_id,
            'module' => 'care',
            'type' => 'care.result_or_summary',
            'title' => $title,
            'url' => $url,
            'hospital_id' => $patient->hospital_id,
        ]);
    }

    private function notifyHospitalRoles(int $hospitalId, array $roles, string $title, string $url): void
    {
        User::whereHas('roles', fn ($query) => $query->whereIn('name', $roles))
            ->whereHas('hospitals', function ($query) use ($hospitalId): void {
                $query->where('hospitals.id', $hospitalId)
                    ->wherePivot('is_active', true);
            })
            ->each(function (User $user) use ($hospitalId, $title, $url): void {
                SuiteNotification::create([
                    'notifiable_type' => User::class,
                    'notifiable_id' => $user->id,
                    'module' => 'care',
                    'type' => 'care.referral_pending',
                    'title' => $title,
                    'url' => $url,
                    'hospital_id' => $hospitalId,
                ]);
            });
    }
}

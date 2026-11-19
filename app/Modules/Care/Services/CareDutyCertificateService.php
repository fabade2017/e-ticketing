<?php

namespace App\Modules\Care\Services;

use App\Models\AuditTrail;
use App\Models\User;
use App\Modules\Care\Models\CareDutyCertificate;
use App\Modules\Care\Models\CarePatient;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CareDutyCertificateService
{
    public const STATUSES = ['FIT', 'RESTRICTED', 'UNFIT'];

    public const NOT_ASSESSED = 'NOT ASSESSED';

    public const REVIEW_DUE = 'REVIEW DUE';

    public function statusFor(CarePatient $patient): string
    {
        $certificate = $patient->dutyCertificates()
            ->current()
            ->latest('issued_at')
            ->first();

        if ($certificate === null) {
            return self::NOT_ASSESSED;
        }

        if ($certificate->review_date !== null && $certificate->review_date->lessThan(now('Africa/Lagos')->toDateString())) {
            return self::REVIEW_DUE;
        }

        return $certificate->status;
    }

    public function issue(CarePatient $patient, User $actor, array $attributes): CareDutyCertificate
    {
        $this->authorizeDoctor($actor, $patient->hospital_id);

        $status = strtoupper((string) ($attributes['status'] ?? ''));

        if (! in_array($status, self::STATUSES, true)) {
            throw ValidationException::withMessages(['status' => 'Duty status must be FIT, RESTRICTED or UNFIT.']);
        }

        if (empty($attributes['statement'])) {
            throw ValidationException::withMessages(['statement' => 'A duty statement is required.']);
        }

        return DB::transaction(function () use ($patient, $actor, $attributes, $status): CareDutyCertificate {
            $previous = $patient->dutyCertificates()->current()->latest('issued_at')->first();

            $certificate = CareDutyCertificate::create([
                'certificate_number' => 'DUTY-'.strtoupper(Str::random(8)),
                'hospital_id' => $patient->hospital_id,
                'patient_id' => $patient->id,
                'visit_id' => $attributes['visit_id'] ?? null,
                'status' => $status,
                'statement' => $attributes['statement'],
                'review_date' => $attributes['review_date'] ?? null,
                'issued_by' => $actor->id,
                'issued_at' => now(),
                'is_current' => true,
            ]);

            if ($previous !== null) {
                $previous->forceFill([
                    'is_current' => false,
                    'superseded_by' => $certificate->id,
                ])->save();
            }

            AuditTrail::record('care.duty_certificate_issued', $certificate, [
                'status' => $status,
            ], $patient->hospital_id);

            return $certificate;
        });
    }

    public function updateAdministrative(CareDutyCertificate $certificate, User $actor, array $attributes): CareDutyCertificate
    {
        if (! $actor->can('maintain duty records')) {
            throw new AuthorizationException('You cannot maintain duty certificate administrative fields.');
        }

        if (! $actor->canAccessHospital($certificate->hospital_id)) {
            throw new AuthorizationException('You cannot act on this hospital.');
        }

        $unexpected = array_diff(array_keys($attributes), ['administrative_note']);

        if ($unexpected !== []) {
            throw ValidationException::withMessages([
                'administrative_note' => 'Records Officers may only maintain administrative fields.',
            ]);
        }

        $certificate->forceFill([
            'administrative_note' => $attributes['administrative_note'] ?? null,
        ])->save();

        AuditTrail::record('care.duty_certificate_administrative_update', $certificate, [], $certificate->hospital_id);

        return $certificate;
    }

    private function authorizeDoctor(User $actor, int $hospitalId): void
    {
        if (! $actor->can('certify duty status') || ! ($actor->hasRole('Doctor') || $actor->hasRole('SuperAdmin'))) {
            throw new AuthorizationException('Only doctors can certify duty status.');
        }

        if (! $actor->canAccessHospital($hospitalId)) {
            throw new AuthorizationException('You cannot act on this hospital.');
        }
    }
}

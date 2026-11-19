<?php

namespace App\Modules\Care\Services;

use App\Models\AuditTrail;
use App\Models\SuiteNotification;
use App\Models\User;
use App\Modules\Care\Models\CarePatient;
use App\Modules\Care\Models\CarePatientSponsor;
use App\Modules\Care\Models\CareRegistration;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CareRegistrationService
{
    public function __construct(public CareFeeService $fees) {}

    public function create(CarePatient $patient, User $actor, array $attributes = []): CareRegistration
    {
        $this->authorizePatientAccess($actor, $patient);

        $sponsor = null;
        if (! empty($attributes['sponsor_id'])) {
            $sponsor = CarePatientSponsor::findOrFail((int) $attributes['sponsor_id']);

            if ($sponsor->patient_id !== $patient->id) {
                throw ValidationException::withMessages(['sponsor_id' => 'The selected sponsor does not belong to this patient.']);
            }
        }

        $relationship = $sponsor?->relationship ?? (string) ($attributes['relationship'] ?? 'sponsor');
        $category = (string) ($attributes['category'] ?? $patient->category);
        $requiresSponsor = $sponsor !== null || $category === 'dependant';

        $quote = $this->fees->quote($patient, [
            'category' => $category,
            'relationship' => $relationship,
            'requires_sponsor' => $requiresSponsor,
            'sponsor_approved' => $sponsor?->is_approved ?? false,
        ]);

        $registration = CareRegistration::create(array_merge([
            'registration_number' => 'REG-'.strtoupper(Str::random(8)),
            'patient_id' => $patient->id,
            'hospital_id' => $patient->hospital_id,
            'sponsor_id' => $sponsor?->id,
            'category' => $category,
            'status' => 'pending',
        ], $quote));

        AuditTrail::record('care.registration_submitted', $registration, [], $registration->hospital_id);

        return $registration;
    }

    public function review(CareRegistration $registration, User $actor, string $decision, array $attributes = []): CareRegistration
    {
        if (! $actor->can('approve registrations')) {
            throw new AuthorizationException('You cannot review care registrations.');
        }

        $this->authorizeHospital($actor, $registration->hospital_id);

        if (! in_array($decision, ['approved', 'rejected'], true)) {
            throw ValidationException::withMessages(['decision' => 'Invalid registration decision.']);
        }

        $identityVerified = (bool) ($attributes['identity_verified'] ?? $registration->identity_verified);
        $evidenceVerified = (bool) ($attributes['evidence_verified'] ?? $registration->evidence_verified);
        $sponsorApproved = (bool) ($attributes['sponsor_approved'] ?? $registration->sponsor_approved);

        if ($sponsorApproved && $registration->sponsor_id === null) {
            $sponsorApproved = false;
        }

        if ($decision === 'approved' && (! $identityVerified || ! $evidenceVerified)) {
            throw ValidationException::withMessages(['identity_verified' => 'Identity and evidence checks are required before approval.']);
        }

        if ($decision === 'approved' && $this->requiresSponsor($registration) && ! $sponsorApproved) {
            throw ValidationException::withMessages(['sponsor_approved' => 'A military sponsor must be approved before this registration can be approved.']);
        }

        $quote = $this->fees->quote($registration->patient, [
            'category' => $registration->category,
            'relationship' => $registration->sponsor?->relationship ?? 'sponsor',
            'requires_sponsor' => $this->requiresSponsor($registration),
            'identity_verified' => $identityVerified,
            'evidence_verified' => $evidenceVerified,
            'sponsor_approved' => $sponsorApproved,
        ]);

        $registration->forceFill(array_merge([
            'status' => $decision,
            'identity_verified' => $identityVerified,
            'evidence_verified' => $evidenceVerified,
            'sponsor_approved' => $sponsorApproved,
            'reviewed_by' => $actor->id,
            'reviewed_at' => now(),
        ], $quote))->save();

        AuditTrail::record('care.registration_reviewed', $registration, [
            'decision' => $decision,
        ], $registration->hospital_id);

        if ($registration->patient->linked_user_id !== null) {
            SuiteNotification::create([
                'notifiable_type' => User::class,
                'notifiable_id' => $registration->patient->linked_user_id,
                'module' => 'care',
                'type' => 'care.registration_reviewed',
                'title' => 'Your registration was '.$decision,
                'url' => '/portal/registrations',
                'hospital_id' => $registration->hospital_id,
            ]);
        }

        return $registration;
    }

    public function approveSponsor(CarePatientSponsor $sponsor, User $actor): CarePatientSponsor
    {
        $isSponsor = $sponsor->sponsor_user_id === $actor->id;

        if (! $isSponsor && ! $actor->can('approve registrations')) {
            throw new AuthorizationException('You cannot approve this sponsorship.');
        }

        if (! $isSponsor) {
            $this->authorizeHospital($actor, $sponsor->patient->hospital_id);
        }

        $sponsor->forceFill([
            'is_approved' => true,
            'approved_by' => $actor->id,
            'approved_at' => now(),
        ])->save();

        AuditTrail::record('care.sponsor_approved', $sponsor, [], $sponsor->patient->hospital_id);

        return $sponsor;
    }

    private function requiresSponsor(CareRegistration $registration): bool
    {
        return $registration->sponsor_id !== null || $registration->category === 'dependant';
    }

    private function authorizePatientAccess(User $actor, CarePatient $patient): void
    {
        if (! $actor->can('use care portal') && ! $actor->can('use care workspace')) {
            throw new AuthorizationException('You cannot submit care registrations.');
        }

        if ($actor->hasRole('SuperAdmin') || $patient->linked_user_id === $actor->id) {
            return;
        }

        if ($actor->can('use care workspace')) {
            $this->authorizeHospital($actor, $patient->hospital_id);

            return;
        }

        $isApprovedSponsor = $patient->sponsors()
            ->approved()
            ->where('sponsor_user_id', $actor->id)
            ->exists();

        if (! $isApprovedSponsor) {
            throw new AuthorizationException('You cannot register this patient.');
        }
    }

    private function authorizeHospital(User $actor, int $hospitalId): void
    {
        if (! $actor->canAccessHospital($hospitalId)) {
            throw new AuthorizationException('You cannot act on this hospital.');
        }
    }
}

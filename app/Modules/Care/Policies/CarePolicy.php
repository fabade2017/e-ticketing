<?php

namespace App\Modules\Care\Policies;

use App\Models\User;
use App\Modules\Care\Models\CareAppointment;
use App\Modules\Care\Models\CareDutyCertificate;
use App\Modules\Care\Models\CareInvoice;
use App\Modules\Care\Models\CareLabResult;
use App\Modules\Care\Models\CarePatient;
use App\Modules\Care\Models\CareRegistration;
use App\Modules\Care\Models\CareVisit;

class CarePolicy
{
    public function accessPortal(User $user): bool
    {
        return $user->can('use care portal');
    }

    public function accessWorkspace(User $user): bool
    {
        return $user->can('use care workspace');
    }

    public function viewPatient(User $user, CarePatient $patient): bool
    {
        if ($user->hasRole('SuperAdmin')) {
            return true;
        }

        if ($patient->linked_user_id === $user->id) {
            return true;
        }

        if ($user->can('use care workspace')) {
            return $user->canAccessHospital($patient->hospital_id);
        }

        return $patient->sponsors()
            ->approved()
            ->where('sponsor_user_id', $user->id)
            ->exists();
    }

    public function viewClinicalHistory(User $user, CarePatient $patient): bool
    {
        if ($user->hasRole('SuperAdmin')) {
            return true;
        }

        return $user->can('view clinical records')
            && $user->can('use care workspace')
            && $user->canAccessHospital($patient->hospital_id);
    }

    public function viewPublishedSummary(User $user, CareVisit $visit): bool
    {
        if ($user->hasRole('SuperAdmin')) {
            return true;
        }

        if ($visit->patient->linked_user_id === $user->id && $visit->published_summary !== null) {
            return true;
        }

        return $user->can('view clinical records')
            && $user->can('use care workspace')
            && $user->canAccessHospital($visit->hospital_id);
    }

    public function viewInvoice(User $user, CareInvoice $invoice): bool
    {
        if ($user->hasRole('SuperAdmin')) {
            return true;
        }

        if ($invoice->patient->linked_user_id === $user->id) {
            return true;
        }

        if ($invoice->patient->sponsors()->approved()->where('sponsor_user_id', $user->id)->exists()) {
            return true;
        }

        return $user->can('use care workspace') && $user->canAccessHospital($invoice->hospital_id);
    }

    public function reviewRegistration(User $user, CareRegistration $registration): bool
    {
        return $user->can('approve registrations')
            && $user->canAccessHospital($registration->hospital_id);
    }

    public function confirmAppointment(User $user, CareAppointment $appointment): bool
    {
        return $user->can('approve registrations')
            && $user->canAccessHospital($appointment->hospital_id);
    }

    public function publishLabResult(User $user, CareLabResult $result): bool
    {
        return $user->can('publish lab results')
            && $user->canAccessHospital($result->hospital_id);
    }

    public function issueDutyCertificate(User $user, CarePatient $patient): bool
    {
        return $user->can('certify duty status')
            && ($user->hasRole('Doctor') || $user->hasRole('SuperAdmin'))
            && $user->canAccessHospital($patient->hospital_id);
    }

    public function maintainDutyRecord(User $user, CareDutyCertificate $certificate): bool
    {
        return $user->can('maintain duty records')
            && $user->canAccessHospital($certificate->hospital_id);
    }

    public function manageHospital(User $user, int $hospitalId): bool
    {
        return $user->canAccessHospital($hospitalId);
    }

    public function viewReport(User $user, ?int $hospitalId): bool
    {
        if (! $user->can('view reports')) {
            return false;
        }

        if ($hospitalId === null) {
            return $user->can('view network reports') || $user->hasRole('SuperAdmin');
        }

        return $user->canAccessHospital($hospitalId);
    }
}

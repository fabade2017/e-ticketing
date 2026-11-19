<?php

namespace App\Modules\Care\Services;

use App\Models\AuditTrail;
use App\Models\SuiteNotification;
use App\Models\User;
use App\Modules\Care\Models\CareAppointment;
use App\Modules\Care\Models\CarePatient;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CareAppointmentService
{
    public function request(CarePatient $patient, User $actor, array $attributes): CareAppointment
    {
        if (! $actor->can('use care portal') && ! $actor->can('use care workspace')) {
            throw new AuthorizationException('You cannot request care appointments.');
        }

        if ($patient->linked_user_id !== $actor->id && ! $actor->hasRole('SuperAdmin')) {
            if (! $actor->can('use care workspace') || ! $actor->canAccessHospital($patient->hospital_id)) {
                throw new AuthorizationException('You cannot request an appointment for this patient.');
            }
        }

        if (empty($attributes['requested_for'])) {
            throw ValidationException::withMessages(['requested_for' => 'A requested date and time is required.']);
        }

        $appointment = CareAppointment::create([
            'appointment_number' => 'APT-'.strtoupper(Str::random(8)),
            'hospital_id' => $patient->hospital_id,
            'patient_id' => $patient->id,
            'requested_by' => $actor->id,
            'requested_for' => $attributes['requested_for'],
            'reason' => $attributes['reason'] ?? null,
            'status' => 'requested',
        ]);

        AuditTrail::record('care.appointment_requested', $appointment, [], $appointment->hospital_id);

        return $appointment;
    }

    public function confirm(CareAppointment $appointment, User $actor): CareAppointment
    {
        if (! $actor->can('approve registrations')) {
            throw new AuthorizationException('You cannot confirm appointments.');
        }

        if (! $actor->canAccessHospital($appointment->hospital_id)) {
            throw new AuthorizationException('You cannot confirm appointments for this hospital.');
        }

        if ($appointment->status !== 'requested') {
            throw ValidationException::withMessages(['status' => 'Only requested appointments can be confirmed.']);
        }

        $appointment->forceFill([
            'status' => 'confirmed',
            'confirmed_by' => $actor->id,
            'confirmed_at' => now(),
        ])->save();

        AuditTrail::record('care.appointment_confirmed', $appointment, [], $appointment->hospital_id);

        if ($appointment->patient->linked_user_id !== null) {
            SuiteNotification::create([
                'notifiable_type' => User::class,
                'notifiable_id' => $appointment->patient->linked_user_id,
                'module' => 'care',
                'type' => 'care.appointment_confirmed',
                'title' => 'Your appointment has been confirmed',
                'url' => '/portal/appointments',
                'hospital_id' => $appointment->hospital_id,
            ]);
        }

        return $appointment;
    }

    public function complete(CareAppointment $appointment, User $actor): CareAppointment
    {
        if ($appointment->status !== 'confirmed') {
            return $appointment;
        }

        $appointment->forceFill(['status' => 'completed'])->save();
        AuditTrail::record('care.appointment_completed', $appointment, [], $appointment->hospital_id);

        return $appointment;
    }
}

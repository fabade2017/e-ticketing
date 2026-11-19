<?php

namespace App\Modules\Care\Models;

use App\Models\Hospital;
use App\Models\User;
use Database\Factories\CareAppointmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CareAppointment extends Model
{
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return CareAppointmentFactory::new();
    }

    protected $fillable = [
        'appointment_number', 'hospital_id', 'patient_id', 'requested_by',
        'requested_for', 'reason', 'status', 'confirmed_by', 'confirmed_at',
    ];

    protected $casts = [
        'requested_for' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(CarePatient::class, 'patient_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function visit(): HasOne
    {
        return $this->hasOne(CareVisit::class, 'appointment_id');
    }

    public function scopeForHospital(Builder $query, int $hospitalId): Builder
    {
        return $query->where('hospital_id', $hospitalId);
    }

    public function scopeRequested(Builder $query): Builder
    {
        return $query->where('status', 'requested');
    }

    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('status', 'confirmed');
    }
}

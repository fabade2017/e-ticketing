<?php

namespace App\Modules\Care\Models;

use App\Models\Hospital;
use App\Models\User;
use Database\Factories\CareVisitFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CareVisit extends Model
{
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return CareVisitFactory::new();
    }

    protected $fillable = [
        'visit_number', 'hospital_id', 'patient_id', 'registration_id', 'appointment_id',
        'visit_type', 'status', 'published_summary', 'internal_notes', 'clinician_id',
        'checked_in_at', 'completed_at',
    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(CarePatient::class, 'patient_id');
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(CareRegistration::class, 'registration_id');
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(CareAppointment::class, 'appointment_id');
    }

    public function clinician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'clinician_id');
    }

    public function vitals(): HasMany
    {
        return $this->hasMany(CareVital::class, 'visit_id');
    }

    public function diagnoses(): HasMany
    {
        return $this->hasMany(CareDiagnosis::class, 'visit_id');
    }

    public function labOrders(): HasMany
    {
        return $this->hasMany(CareLabOrder::class, 'visit_id');
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(CarePrescription::class, 'visit_id');
    }

    public function scopeForHospital(Builder $query, int $hospitalId): Builder
    {
        return $query->where('hospital_id', $hospitalId);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_summary');
    }
}

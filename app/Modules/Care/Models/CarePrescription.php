<?php

namespace App\Modules\Care\Models;

use App\Models\Hospital;
use App\Models\User;
use Database\Factories\CarePrescriptionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CarePrescription extends Model
{
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return CarePrescriptionFactory::new();
    }

    protected $fillable = [
        'visit_id', 'hospital_id', 'patient_id', 'prescribed_by', 'medication',
        'dosage', 'frequency', 'duration_days', 'quantity', 'instructions', 'status',
    ];

    protected $casts = [
        'duration_days' => 'integer',
        'quantity' => 'integer',
    ];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(CareVisit::class, 'visit_id');
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(CarePatient::class, 'patient_id');
    }

    public function prescribedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prescribed_by');
    }

    public function dispenses(): HasMany
    {
        return $this->hasMany(CarePharmacyDispense::class, 'prescription_id');
    }

    public function scopeForHospital(Builder $query, int $hospitalId): Builder
    {
        return $query->where('hospital_id', $hospitalId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}

<?php

namespace App\Modules\Care\Models;

use App\Models\Hospital;
use Database\Factories\BedFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bed extends Model
{
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return BedFactory::new();
    }

    protected $fillable = [
        'hospital_id', 'ward_id', 'code', 'status', 'current_patient_id',
    ];

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class, 'ward_id');
    }

    public function currentPatient(): BelongsTo
    {
        return $this->belongsTo(CarePatient::class, 'current_patient_id');
    }

    public function admissions(): HasMany
    {
        return $this->hasMany(CareAdmission::class, 'bed_id');
    }

    public function scopeForHospital(Builder $query, int $hospitalId): Builder
    {
        return $query->where('hospital_id', $hospitalId);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', 'available');
    }
}

<?php

namespace App\Modules\Care\Models;

use App\Models\Hospital;
use App\Models\User;
use Database\Factories\CareDiagnosisFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareDiagnosis extends Model
{
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return CareDiagnosisFactory::new();
    }

    protected $fillable = [
        'visit_id', 'hospital_id', 'patient_id', 'diagnosed_by', 'code',
        'description', 'type', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
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

    public function diagnosedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diagnosed_by');
    }

    public function scopeForHospital(Builder $query, int $hospitalId): Builder
    {
        return $query->where('hospital_id', $hospitalId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}

<?php

namespace App\Modules\Care\Models;

use App\Models\Hospital;
use App\Models\User;
use Database\Factories\CareVitalFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareVital extends Model
{
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return CareVitalFactory::new();
    }

    protected $fillable = [
        'visit_id', 'hospital_id', 'recorded_by', 'height_cm', 'weight_kg',
        'temperature_c', 'pulse', 'bp_systolic', 'bp_diastolic',
        'respiratory_rate', 'oxygen_saturation', 'recorded_at',
    ];

    protected $casts = [
        'height_cm' => 'decimal:2',
        'weight_kg' => 'decimal:2',
        'temperature_c' => 'decimal:2',
        'recorded_at' => 'datetime',
    ];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(CareVisit::class, 'visit_id');
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function scopeForHospital(Builder $query, int $hospitalId): Builder
    {
        return $query->where('hospital_id', $hospitalId);
    }
}

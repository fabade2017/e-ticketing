<?php

namespace App\Modules\Care\Models;

use App\Models\Hospital;
use App\Models\User;
use Database\Factories\CareReferralFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareReferral extends Model
{
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return CareReferralFactory::new();
    }

    protected $fillable = [
        'referral_number', 'from_hospital_id', 'to_hospital_id', 'patient_id',
        'visit_id', 'requested_by', 'reason', 'status', 'accepted_by',
        'accepted_at', 'completed_at',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function fromHospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class, 'from_hospital_id');
    }

    public function toHospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class, 'to_hospital_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(CarePatient::class, 'patient_id');
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(CareVisit::class, 'visit_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    public function scopeForHospital(Builder $query, int $hospitalId): Builder
    {
        return $query->where('from_hospital_id', $hospitalId)
            ->orWhere('to_hospital_id', $hospitalId);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeAccepted(Builder $query): Builder
    {
        return $query->where('status', 'accepted');
    }
}

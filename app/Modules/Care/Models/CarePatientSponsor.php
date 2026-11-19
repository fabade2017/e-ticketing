<?php

namespace App\Modules\Care\Models;

use App\Models\User;
use Database\Factories\CarePatientSponsorFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarePatientSponsor extends Model
{
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return CarePatientSponsorFactory::new();
    }

    protected $fillable = [
        'patient_id', 'sponsor_user_id', 'sponsor_patient_id', 'relationship',
        'is_approved', 'approved_by', 'approved_at',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
        'approved_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(CarePatient::class, 'patient_id');
    }

    public function sponsorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sponsor_user_id');
    }

    public function sponsorPatient(): BelongsTo
    {
        return $this->belongsTo(CarePatient::class, 'sponsor_patient_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('is_approved', true);
    }
}

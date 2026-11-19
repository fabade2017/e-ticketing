<?php

namespace App\Modules\Care\Models;

use App\Models\Hospital;
use App\Models\User;
use Database\Factories\CareRegistrationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CareRegistration extends Model
{
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return CareRegistrationFactory::new();
    }

    protected $fillable = [
        'registration_number', 'patient_id', 'hospital_id', 'sponsor_id', 'category',
        'status', 'identity_verified', 'evidence_verified', 'sponsor_approved',
        'fee_amount', 'waiver_amount', 'payable_amount', 'waiver_reason',
        'reviewed_by', 'reviewed_at',
    ];

    protected $casts = [
        'identity_verified' => 'boolean',
        'evidence_verified' => 'boolean',
        'sponsor_approved' => 'boolean',
        'fee_amount' => 'decimal:2',
        'waiver_amount' => 'decimal:2',
        'payable_amount' => 'decimal:2',
        'reviewed_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(CarePatient::class, 'patient_id');
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(CarePatientSponsor::class, 'sponsor_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CareDocument::class, 'registration_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(CareInvoice::class, 'registration_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopeForHospital(Builder $query, int $hospitalId): Builder
    {
        return $query->where('hospital_id', $hospitalId);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }
}

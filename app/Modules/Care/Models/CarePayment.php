<?php

namespace App\Modules\Care\Models;

use App\Models\Hospital;
use App\Models\User;
use Database\Factories\CarePaymentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarePayment extends Model
{
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return CarePaymentFactory::new();
    }

    protected $fillable = [
        'payment_reference', 'invoice_id', 'hospital_id', 'patient_id', 'amount',
        'method', 'bank_reference', 'receipt_path', 'credited', 'status',
        'submitted_by', 'paid_at', 'verified_by', 'verified_at', 'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'credited' => 'boolean',
        'paid_at' => 'datetime',
        'verified_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(CareInvoice::class, 'invoice_id');
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(CarePatient::class, 'patient_id');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function scopeForHospital(Builder $query, int $hospitalId): Builder
    {
        return $query->where('hospital_id', $hospitalId);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('status', 'verified');
    }
}

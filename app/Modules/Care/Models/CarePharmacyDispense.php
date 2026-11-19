<?php

namespace App\Modules\Care\Models;

use App\Models\Hospital;
use App\Models\User;
use Database\Factories\CarePharmacyDispenseFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarePharmacyDispense extends Model
{
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return CarePharmacyDispenseFactory::new();
    }

    protected $fillable = [
        'prescription_id', 'hospital_id', 'patient_id', 'dispensed_by',
        'quantity', 'notes', 'dispensed_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'dispensed_at' => 'datetime',
    ];

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(CarePrescription::class, 'prescription_id');
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(CarePatient::class, 'patient_id');
    }

    public function dispensedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispensed_by');
    }
}

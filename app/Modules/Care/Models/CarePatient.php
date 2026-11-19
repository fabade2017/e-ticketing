<?php

namespace App\Modules\Care\Models;

use App\Models\Hospital;
use App\Models\User;
use Database\Factories\CarePatientFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CarePatient extends Model
{
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return CarePatientFactory::new();
    }

    protected $fillable = [
        'network_number', 'army_number', 'category', 'hospital_id', 'linked_user_id',
        'first_name', 'last_name', 'date_of_birth', 'gender', 'phone', 'address', 'is_active',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'is_active' => 'boolean',
    ];

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function linkedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'linked_user_id');
    }

    public function sponsors(): HasMany
    {
        return $this->hasMany(CarePatientSponsor::class, 'patient_id');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(CareRegistration::class, 'patient_id');
    }

    public function visits(): HasMany
    {
        return $this->hasMany(CareVisit::class, 'patient_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(CareAppointment::class, 'patient_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(CareInvoice::class, 'patient_id');
    }

    public function dutyCertificates(): HasMany
    {
        return $this->hasMany(CareDutyCertificate::class, 'patient_id');
    }

    public function scopeForHospital(Builder $query, int $hospitalId): Builder
    {
        return $query->where('hospital_id', $hospitalId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }
}

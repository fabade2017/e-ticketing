<?php

namespace App\Modules\Care\Models;

use App\Models\Hospital;
use App\Models\User;
use Database\Factories\CareDocumentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareDocument extends Model
{
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return CareDocumentFactory::new();
    }

    protected $fillable = [
        'hospital_id', 'patient_id', 'registration_id', 'document_type', 'path',
        'original_name', 'mime_type', 'size', 'uploaded_by', 'is_verified',
    ];

    protected $casts = [
        'size' => 'integer',
        'is_verified' => 'boolean',
    ];

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(CarePatient::class, 'patient_id');
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(CareRegistration::class, 'registration_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function scopeForHospital(Builder $query, int $hospitalId): Builder
    {
        return $query->where('hospital_id', $hospitalId);
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('is_verified', true);
    }
}

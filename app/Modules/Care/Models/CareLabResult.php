<?php

namespace App\Modules\Care\Models;

use App\Models\Hospital;
use App\Models\User;
use Database\Factories\CareLabResultFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CareLabResult extends Model
{
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return CareLabResultFactory::new();
    }

    protected $fillable = [
        'lab_order_id', 'hospital_id', 'patient_id', 'result_by', 'findings',
        'status', 'published_by', 'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function labOrder(): BelongsTo
    {
        return $this->belongsTo(CareLabOrder::class, 'lab_order_id');
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(CarePatient::class, 'patient_id');
    }

    public function resultBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'result_by');
    }

    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function images(): HasMany
    {
        return $this->hasMany(CareLabImage::class, 'lab_result_id');
    }

    public function scopeForHospital(Builder $query, int $hospitalId): Builder
    {
        return $query->where('hospital_id', $hospitalId);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }
}

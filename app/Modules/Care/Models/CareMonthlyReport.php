<?php

namespace App\Modules\Care\Models;

use App\Models\Hospital;
use App\Models\User;
use Database\Factories\CareMonthlyReportFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareMonthlyReport extends Model
{
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return CareMonthlyReportFactory::new();
    }

    protected $fillable = [
        'hospital_id', 'scope', 'scope_key', 'report_month', 'distinct_patients',
        'completed_visits', 'patients_tested', 'tests_performed',
        'disease_encounters', 'generated_by', 'generated_at', 'metadata',
    ];

    protected $casts = [
        'report_month' => 'date',
        'generated_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function scopeForHospital(Builder $query, int $hospitalId): Builder
    {
        return $query->where('hospital_id', $hospitalId);
    }

    public function scopeNetwork(Builder $query): Builder
    {
        return $query->where('scope', 'network');
    }

    public function scopeForMonth(Builder $query, string $month): Builder
    {
        return $query->where('report_month', $month);
    }
}

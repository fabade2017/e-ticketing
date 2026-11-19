<?php

namespace App\Modules\Maintenance\Models;

use Database\Factories\MaintPreventiveScheduleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintPreventiveSchedule extends Model
{
    /** @use HasFactory<MaintPreventiveScheduleFactory> */
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return MaintPreventiveScheduleFactory::new();
    }

    protected $fillable = [
        'hospital_id', 'equipment_id', 'title', 'interval_days', 'next_due_at',
        'last_work_order_id', 'is_active',
    ];

    protected $casts = [
        'next_due_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function scopeDue(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('next_due_at', '<=', now());
    }
}

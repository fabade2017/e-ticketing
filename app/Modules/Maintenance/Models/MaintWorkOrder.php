<?php

namespace App\Modules\Maintenance\Models;

use Database\Factories\MaintWorkOrderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MaintWorkOrder extends Model
{
    /** @use HasFactory<MaintWorkOrderFactory> */
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return MaintWorkOrderFactory::new();
    }

    public const OPEN_STATUSES = ['pending', 'assigned', 'in_progress'];

    protected $fillable = [
        'work_order_number', 'hospital_id', 'equipment_id', 'fault_id', 'ticket_id', 'fault_code',
        'title', 'description', 'priority', 'status', 'assigned_to', 'requested_by',
        'due_at', 'started_at', 'completed_at',
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function fault(): BelongsTo
    {
        return $this->belongsTo(Fault::class);
    }

    public function parts(): HasMany
    {
        return $this->hasMany(MaintWorkOrderPart::class, 'work_order_id');
    }

    public function serviceLogs(): HasMany
    {
        return $this->hasMany(MaintServiceLog::class, 'work_order_id');
    }

    public function overdueFlag(): HasOne
    {
        return $this->hasOne(MaintOverdueFlag::class, 'work_order_id');
    }

    public function scopeForHospital(Builder $query, int $hospitalId): Builder
    {
        return $query->where('hospital_id', $hospitalId);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', self::OPEN_STATUSES);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereIn('status', self::OPEN_STATUSES)
            ->whereNotNull('due_at')
            ->where('due_at', '<', now());
    }
}

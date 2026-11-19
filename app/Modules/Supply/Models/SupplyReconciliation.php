<?php

namespace App\Modules\Supply\Models;

use Database\Factories\SupplyReconciliationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplyReconciliation extends Model
{
    /** @use HasFactory<SupplyReconciliationFactory> */
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return SupplyReconciliationFactory::new();
    }

    protected $fillable = [
        'hospital_id', 'item_id', 'expected_quantity', 'counted_quantity', 'difference',
        'idempotency_key', 'status', 'notes', 'requested_by', 'approved_by', 'approved_at',
    ];

    protected $casts = [
        'expected_quantity' => 'decimal:3',
        'counted_quantity' => 'decimal:3',
        'difference' => 'decimal:3',
        'approved_at' => 'datetime',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(SupplyItem::class);
    }

    public function scopeForHospital(Builder $query, int $hospitalId): Builder
    {
        return $query->where('hospital_id', $hospitalId);
    }
}

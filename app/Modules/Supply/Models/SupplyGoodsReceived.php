<?php

namespace App\Modules\Supply\Models;

use Database\Factories\SupplyGoodsReceivedFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplyGoodsReceived extends Model
{
    /** @use HasFactory<SupplyGoodsReceivedFactory> */
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return SupplyGoodsReceivedFactory::new();
    }

    protected $table = 'supply_goods_received';

    protected $fillable = [
        'hospital_id', 'item_id', 'batch_id', 'quantity', 'supplier', 'document_no',
        'idempotency_key', 'status', 'received_by', 'approved_by', 'approved_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'approved_at' => 'datetime',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(SupplyItem::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(SupplyBatch::class);
    }

    public function scopeForHospital(Builder $query, int $hospitalId): Builder
    {
        return $query->where('hospital_id', $hospitalId);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }
}

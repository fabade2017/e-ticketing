<?php

namespace App\Modules\Supply\Models;

use App\Models\Hospital;
use Database\Factories\SupplyStockBalanceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplyStockBalance extends Model
{
    /** @use HasFactory<SupplyStockBalanceFactory> */
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return SupplyStockBalanceFactory::new();
    }

    protected $fillable = ['item_id', 'hospital_id', 'store', 'quantity_on_hand', 'quantity_reserved'];

    protected $casts = [
        'quantity_on_hand' => 'decimal:3',
        'quantity_reserved' => 'decimal:3',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(SupplyItem::class);
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function scopeForHospital(Builder $query, int $hospitalId): Builder
    {
        return $query->where('hospital_id', $hospitalId);
    }
}

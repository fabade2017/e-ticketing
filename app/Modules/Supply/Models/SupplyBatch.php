<?php

namespace App\Modules\Supply\Models;

use Database\Factories\SupplyBatchFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplyBatch extends Model
{
    /** @use HasFactory<SupplyBatchFactory> */
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return SupplyBatchFactory::new();
    }

    protected $fillable = ['item_id', 'hospital_id', 'batch_code', 'expires_on', 'is_damaged', 'quantity'];

    protected $casts = [
        'expires_on' => 'date',
        'is_damaged' => 'boolean',
        'quantity' => 'decimal:3',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(SupplyItem::class);
    }

    public function scopeForHospital(Builder $query, int $hospitalId): Builder
    {
        return $query->where('hospital_id', $hospitalId);
    }

    public function scopeDamaged(Builder $query): Builder
    {
        return $query->where('is_damaged', true);
    }

    public function scopeExpiringSoon(Builder $query, int $days = 30): Builder
    {
        return $query->whereNotNull('expires_on')
            ->where('expires_on', '<=', now()->addDays($days)->toDateString());
    }
}

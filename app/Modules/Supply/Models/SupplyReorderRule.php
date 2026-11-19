<?php

namespace App\Modules\Supply\Models;

use Database\Factories\SupplyReorderRuleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplyReorderRule extends Model
{
    /** @use HasFactory<SupplyReorderRuleFactory> */
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return SupplyReorderRuleFactory::new();
    }

    protected $fillable = ['hospital_id', 'item_id', 'minimum_quantity', 'target_quantity', 'is_active'];

    protected $casts = [
        'minimum_quantity' => 'decimal:3',
        'target_quantity' => 'decimal:3',
        'is_active' => 'boolean',
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

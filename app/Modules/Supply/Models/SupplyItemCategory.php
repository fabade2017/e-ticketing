<?php

namespace App\Modules\Supply\Models;

use Database\Factories\SupplyItemCategoryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplyItemCategory extends Model
{
    /** @use HasFactory<SupplyItemCategoryFactory> */
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return SupplyItemCategoryFactory::new();
    }

    protected $fillable = ['name', 'code', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function items(): HasMany
    {
        return $this->hasMany(SupplyItem::class, 'supply_item_category_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}

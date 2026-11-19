<?php

namespace App\Modules\Supply\Models;

use Database\Factories\SupplyItemFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplyItem extends Model
{
    /** @use HasFactory<SupplyItemFactory> */
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return SupplyItemFactory::new();
    }

    protected $fillable = ['name', 'sku', 'category', 'unit', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}

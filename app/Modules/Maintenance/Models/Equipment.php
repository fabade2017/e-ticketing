<?php

namespace App\Modules\Maintenance\Models;

use Database\Factories\EquipmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Equipment extends Model
{
    /** @use HasFactory<EquipmentFactory> */
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return EquipmentFactory::new();
    }

    protected $fillable = ['hospital_id', 'asset_id', 'name', 'unit', 'model', 'location', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function faults(): HasMany
    {
        return $this->hasMany(Fault::class);
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(MaintWorkOrder::class);
    }

    public function scopeForHospital(Builder $query, int $hospitalId): Builder
    {
        return $query->where('hospital_id', $hospitalId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}

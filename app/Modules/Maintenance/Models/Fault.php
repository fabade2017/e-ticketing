<?php

namespace App\Modules\Maintenance\Models;

use Database\Factories\FaultFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Fault extends Model
{
    /** @use HasFactory<FaultFactory> */
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return FaultFactory::new();
    }

    protected $fillable = ['hospital_id', 'equipment_id', 'code', 'title', 'description', 'reported_by', 'status'];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function scopeForHospital(Builder $query, int $hospitalId): Builder
    {
        return $query->where('hospital_id', $hospitalId);
    }
}

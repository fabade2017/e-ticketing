<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SuiteLink extends Model
{
    protected $fillable = [
        'source_type',
        'source_id',
        'target_type',
        'target_id',
        'type',
        'created_by',
    ];

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function target(): MorphTo
    {
        return $this->morphTo();
    }

    public static function connect(Model $source, Model $target, string $type): self
    {
        return static::firstOrCreate([
            'source_type' => $source->getMorphClass(),
            'source_id' => $source->getKey(),
            'target_type' => $target->getMorphClass(),
            'target_id' => $target->getKey(),
            'type' => $type,
        ], [
            'created_by' => auth()->id(),
        ]);
    }
}

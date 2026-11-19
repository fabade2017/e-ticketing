<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Context;

class AuditTrail extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'subject_type',
        'subject_id',
        'action',
        'hospital_id',
        'ip',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public static function record(string $action, ?Model $subject = null, array $metadata = [], ?int $hospitalId = null): self
    {
        $contextHospital = Context::get('hospital_id');

        return static::create([
            'user_id' => Auth::id(),
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'action' => $action,
            'hospital_id' => $hospitalId ?? (is_numeric($contextHospital) ? (int) $contextHospital : null),
            'ip' => request()->ip(),
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }
}

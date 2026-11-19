<?php

namespace App\Modules\Supply\Models;

use App\Models\User;
use Database\Factories\SupplyApprovalRequestFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SupplyApprovalRequest extends Model
{
    /** @use HasFactory<SupplyApprovalRequestFactory> */
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return SupplyApprovalRequestFactory::new();
    }

    protected $fillable = [
        'hospital_id', 'subject_type', 'subject_id', 'type', 'status',
        'requested_by', 'approved_by', 'notes',
    ];

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function scopeForHospital(Builder $query, int $hospitalId): Builder
    {
        return $query->where('hospital_id', $hospitalId);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }
}

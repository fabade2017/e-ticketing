<?php

namespace App\Models;

use Database\Factories\SuiteNotificationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SuiteNotification extends Model
{
    /** @use HasFactory<SuiteNotificationFactory> */
    use HasFactory;

    protected $fillable = [
        'notifiable_type',
        'notifiable_id',
        'module',
        'type',
        'title',
        'url',
        'hospital_id',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function scopeForModule(Builder $query, string $module): Builder
    {
        return $query->where('module', $module);
    }

    public function markRead(): bool
    {
        return $this->update(['read_at' => now()]);
    }
}

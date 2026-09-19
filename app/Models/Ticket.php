<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
    protected $fillable = [
        'ticket_number',
        'title',
        'description',
        'category',
        'department_id',
        'priority',
        'sla_due_at',
        'escalated_at',
        'status',
        'user_id',
        'assigned_to',
        'location',
        'unit',
        'offline_created',
        'closed_at',
    ];

    protected $casts = [
        'offline_created' => 'boolean',
        'closed_at' => 'datetime',
        'sla_due_at' => 'datetime',
        'escalated_at' => 'datetime',
    ];

    public const SLA_HOURS = ['urgent'=>4, 'high'=>12, 'medium'=>24, 'low'=>72];

    protected static function booted(): void
    {
        static::creating(function (Ticket $ticket) {
            if (empty($ticket->ticket_number)) {
                $ticket->ticket_number = 'NA-' . date('Y') . '-' . strtoupper(\Illuminate\Support\Str::random(8));
            }
            if (empty($ticket->sla_due_at) && !empty($ticket->priority)) {
                $hours = self::SLA_HOURS[$ticket->priority] ?? 24;
                $ticket->sla_due_at = now()->addHours($hours);
            }
        });
        static::updating(function (Ticket $ticket) {
            if ($ticket->isDirty('priority') && empty($ticket->isDirty('sla_due_at'))) {
                $hours = self::SLA_HOURS[$ticket->priority] ?? 24;
                $ticket->sla_due_at = now()->addHours($hours);
            }
        });
    }
    public function histories() { return $this->hasMany(TicketHistory::class); }
    public function isOverdue(): bool { return $this->sla_due_at && $this->sla_due_at->isPast() && !in_array($this->status, ['resolved','closed']); }
    public function slaHours(): ?int { return self::SLA_HOURS[$this->priority] ?? null; }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
}

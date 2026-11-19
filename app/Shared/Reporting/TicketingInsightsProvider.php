<?php

namespace App\Shared\Reporting;

use App\Models\Ticket;
use App\Models\User;

class TicketingInsightsProvider implements InsightsProvider
{
    public function key(): string
    {
        return 'ticketing';
    }

    public function label(): string
    {
        return 'Ticketing';
    }

    public function permission(): ?string
    {
        return 'use ticketing';
    }

    public function metrics(User $user, bool $includeAll): array
    {
        $query = Ticket::query();

        if (! $includeAll) {
            $query->visibleTo($user);
        }

        return [
            [
                'label' => 'Open tickets',
                'value' => (clone $query)->where('status', 'open')->count(),
                'url' => '/tickets',
            ],
            [
                'label' => 'Overdue tickets',
                'value' => (clone $query)
                    ->whereNotNull('sla_due_at')
                    ->where('sla_due_at', '<', now())
                    ->whereNotIn('status', ['resolved', 'closed'])
                    ->count(),
                'url' => '/tickets?status=overdue',
            ],
        ];
    }
}

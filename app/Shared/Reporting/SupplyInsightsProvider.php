<?php

namespace App\Shared\Reporting;

use App\Models\User;
use App\Modules\Supply\Services\SupplyAlertService;

class SupplyInsightsProvider implements InsightsProvider
{
    public function __construct(public SupplyAlertService $alerts) {}

    public function key(): string
    {
        return 'supply';
    }

    public function label(): string
    {
        return 'Supply';
    }

    public function permission(): ?string
    {
        return 'use supply';
    }

    public function metrics(User $user, bool $includeAll): array
    {
        return $this->alerts->metrics($user, $includeAll);
    }
}

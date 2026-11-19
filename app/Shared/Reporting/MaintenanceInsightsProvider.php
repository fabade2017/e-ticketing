<?php

namespace App\Shared\Reporting;

use App\Models\User;
use App\Modules\Maintenance\Services\MaintenanceAlertService;

class MaintenanceInsightsProvider implements InsightsProvider
{
    public function __construct(public MaintenanceAlertService $alerts) {}

    public function key(): string
    {
        return 'maintenance';
    }

    public function label(): string
    {
        return 'Maintenance';
    }

    public function permission(): ?string
    {
        return 'use maintenance';
    }

    public function metrics(User $user, bool $includeAll): array
    {
        return $this->alerts->metrics($user, $includeAll);
    }
}

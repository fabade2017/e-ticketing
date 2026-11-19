<?php

namespace App\Shared\Reporting;

use App\Models\User;
use App\Modules\Care\Services\CareAlertService;

class CareInsightsProvider implements InsightsProvider
{
    public function __construct(public CareAlertService $alerts) {}

    public function key(): string
    {
        return 'care';
    }

    public function label(): string
    {
        return 'Care Network';
    }

    public function permission(): ?string
    {
        return 'use care workspace';
    }

    public function metrics(User $user, bool $includeAll): array
    {
        return $this->alerts->metrics($user, $includeAll);
    }
}

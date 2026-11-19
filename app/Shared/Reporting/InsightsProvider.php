<?php

namespace App\Shared\Reporting;

use App\Models\User;

interface InsightsProvider
{
    public function key(): string;

    public function label(): string;

    public function permission(): ?string;

    /**
     * @return array<int, array{label: string, value: mixed, url: ?string}>
     */
    public function metrics(User $user, bool $includeAll): array;
}

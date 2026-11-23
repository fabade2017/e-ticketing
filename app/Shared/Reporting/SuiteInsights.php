<?php

namespace App\Shared\Reporting;

use App\Models\User;

class SuiteInsights
{
    /**
     * @return array<int, array{key: string, label: string, metrics: array<int, array{label: string, value: mixed, url: ?string}>}>
     */
    public function for(User $user, bool $includeAll): array
    {
        return self::build($user, $includeAll);
    }

    /**
     * @return array<int, array{key: string, label: string, metrics: array<int, array{label: string, value: mixed, url: ?string}>}>
     */
    public static function build(User $user, bool $includeAll): array
    {
        return once(function () use ($user, $includeAll): array {
            return collect(config('suite.insights_providers', []))
                ->map(fn (string $providerClass): InsightsProvider => app($providerClass))
                ->filter(fn (InsightsProvider $provider): bool => $provider->permission() === null || $user->can($provider->permission()))
                ->map(fn (InsightsProvider $provider): array => [
                    'key' => $provider->key(),
                    'label' => $provider->label(),
                    'metrics' => $provider->metrics($user, $includeAll),
                ])
                ->values()
                ->all();
        });
    }
}

<?php

namespace App\Shared\Navigation;

use App\Models\User;

class SuiteNavigation
{
    /**
     * @return array<int, array{key: string, label: string, route: string, icon: string}>
     */
    public function modulesFor(?User $user): array
    {
        if ($user === null) {
            return [];
        }

        return collect(config('suite.modules', []))
            ->filter(fn (array $module): bool => $user->can($module['permission']))
            ->map(fn (array $module, string $key): array => [
                'key' => $key,
                'label' => $module['label'],
                'route' => $module['route'],
                'icon' => $module['icon'],
            ])
            ->values()
            ->all();
    }
}

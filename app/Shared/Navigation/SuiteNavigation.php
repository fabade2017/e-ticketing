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

    /**
     * @return array<int, array{key: string, label: string, route: string, icon: string, description: string, menus: array<int, array{label: string, route: string}>}>
     */
    public function roomsFor(?User $user): array
    {
        if ($user === null) {
            return [];
        }

        return collect(config('suite.modules', []))
            ->except('care-portal')
            ->filter(fn (array $module): bool => collect($module['room_permissions'] ?? [$module['permission']])
                ->contains(fn (string $permission): bool => $user->can($permission)))
            ->map(fn (array $module, string $key): array => [
                'key' => $key,
                'label' => $module['label'],
                'route' => $module['route'],
                'icon' => $module['icon'],
                'description' => $module['description'] ?? '',
                'menus' => collect($module['menus'] ?? [])
                    ->filter(fn (array $menu): bool => $this->canSeeRoomMenu($user, $menu))
                    ->map(fn (array $menu): array => [
                        'label' => $menu['label'],
                        'route' => $menu['route'],
                    ])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array{permission?: string, roles?: array<int, string>}  $menu
     */
    private function canSeeRoomMenu(User $user, array $menu): bool
    {
        if (! empty($menu['permission']) && ! $user->can($menu['permission'])) {
            return false;
        }

        if (! empty($menu['roles']) && ! $user->hasAnyRole($menu['roles'])) {
            return false;
        }

        return true;
    }
}

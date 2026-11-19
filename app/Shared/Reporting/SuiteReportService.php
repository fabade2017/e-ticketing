<?php

namespace App\Shared\Reporting;

use App\Models\User;

class SuiteReportService
{
    public function __construct(public SuiteInsights $insights) {}

    public function sections(User $user, bool $includeAll, ?string $module = null): array
    {
        return collect($this->insights->for($user, $includeAll))
            ->when($module !== null && $module !== 'all', fn ($sections) => $sections->where('key', $module))
            ->values()
            ->all();
    }

    public function availableModules(User $user): array
    {
        $reportModules = ['ticketing', 'care', 'maintenance', 'supply'];

        $modules = collect(config('suite.modules', []))
            ->filter(fn (array $module, string $key): bool => in_array($key, $reportModules, true) && $user->can($module['permission']))
            ->map(fn (array $module, string $key): array => ['key' => $key, 'label' => $module['label']]);

        if ($this->canExport($user, 'all')) {
            $modules->prepend(['key' => 'all', 'label' => 'All permitted modules']);
        }

        return $modules->values()->all();
    }

    public function canExport(User $user, string $module): bool
    {
        if ($module === 'all') {
            return $user->hasRole('SuperAdmin') || $user->can('view network reports');
        }

        $config = config("suite.modules.$module");

        if ($config === null) {
            return false;
        }

        return $user->can($config['permission']);
    }
}

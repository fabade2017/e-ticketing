<?php

namespace App\Modules\Maintenance\Services;

use App\Models\Hospital;
use App\Models\SuiteNotification;
use App\Models\User;
use App\Modules\Maintenance\Models\MaintOverdueFlag;
use App\Modules\Maintenance\Models\MaintWorkOrder;
use Illuminate\Support\Collection;

class MaintenanceAlertService
{
    public function refresh(): int
    {
        $overdue = MaintWorkOrder::overdue()
            ->with('equipment')
            ->whereDoesntHave('overdueFlag', fn ($query) => $query->whereNull('cleared_at'))
            ->get();

        foreach ($overdue as $workOrder) {
            $flag = MaintOverdueFlag::firstOrCreate([
                'work_order_id' => $workOrder->id,
            ], [
                'flagged_at' => now(),
            ]);

            if ($flag->notified_at !== null) {
                continue;
            }

            foreach ($this->recipientsFor($workOrder->hospital_id) as $user) {
                SuiteNotification::firstOrCreate([
                    'notifiable_type' => $user->getMorphClass(),
                    'notifiable_id' => $user->id,
                    'module' => 'maintenance',
                    'type' => 'maintenance.work_order_overdue',
                    'title' => "Overdue work order: {$workOrder->work_order_number}",
                    'url' => "/maintenance/{$workOrder->id}",
                    'hospital_id' => $workOrder->hospital_id,
                ], [
                    'read_at' => null,
                ]);
            }

            $flag->forceFill(['notified_at' => now()])->save();
        }

        return $overdue->count();
    }

    /**
     * @return array<int, array{label: string, value: mixed, url: ?string}>
     */
    public function metrics(User $user, bool $includeAll): array
    {
        $hospitalIds = $this->hospitalIdsFor($user, $includeAll);

        if ($hospitalIds->isEmpty()) {
            return [
                ['label' => 'Open work orders', 'value' => 0, 'url' => '/maintenance'],
                ['label' => 'Overdue work orders', 'value' => 0, 'url' => '/maintenance?status=overdue'],
            ];
        }

        $query = MaintWorkOrder::whereIn('hospital_id', $hospitalIds->all());

        return [
            ['label' => 'Open work orders', 'value' => (clone $query)->open()->count(), 'url' => '/maintenance'],
            ['label' => 'Overdue work orders', 'value' => (clone $query)->overdue()->count(), 'url' => '/maintenance?status=overdue'],
        ];
    }

    private function recipientsFor(int $hospitalId): Collection
    {
        return User::all()->filter(fn (User $user): bool => $user->can('use maintenance') && $user->canAccessHospital($hospitalId));
    }

    private function hospitalIdsFor(User $user, bool $includeAll): Collection
    {
        if ($includeAll && ($user->hasRole('SuperAdmin') || $user->can('view network reports'))) {
            return Hospital::active()->pluck('id');
        }

        return collect([$user->current_hospital_id])->filter();
    }
}

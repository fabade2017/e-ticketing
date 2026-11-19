<?php

namespace App\Modules\Supply\Services;

use App\Models\Hospital;
use App\Models\SuiteNotification;
use App\Models\User;
use App\Modules\Supply\Models\SupplyBatch;
use App\Modules\Supply\Models\SupplyStockBalance;
use Illuminate\Support\Collection;

class SupplyAlertService
{
    /**
     * @return array{low_stock: int, damaged: int, expiring: int}
     */
    public function refresh(int $hospitalId): array
    {
        $lowStock = $this->lowStockRows($hospitalId);
        $damaged = SupplyBatch::forHospital($hospitalId)->damaged()->get();
        $expiring = SupplyBatch::forHospital($hospitalId)
            ->where('is_damaged', false)
            ->expiringSoon()
            ->get();

        foreach ($this->recipientsFor($hospitalId) as $user) {
            foreach ($lowStock as $balance) {
                $this->notify($user, $hospitalId, 'supply.low_stock', "Low stock: {$balance->item->name}", "/supply?item_id={$balance->item_id}");
            }

            foreach ($damaged as $batch) {
                $this->notify($user, $hospitalId, 'supply.damaged_stock', "Damaged batch: {$batch->batch_code}", "/supply?item_id={$batch->item_id}");
            }

            foreach ($expiring as $batch) {
                $this->notify($user, $hospitalId, 'supply.expiring_stock', "Expiring batch: {$batch->batch_code}", "/supply?item_id={$batch->item_id}");
            }
        }

        return [
            'low_stock' => $lowStock->count(),
            'damaged' => $damaged->count(),
            'expiring' => $expiring->count(),
        ];
    }

    /**
     * @return array<int, array{label: string, value: mixed, url: ?string}>
     */
    public function metrics(User $user, bool $includeAll): array
    {
        $hospitalIds = $this->hospitalIdsFor($user, $includeAll);

        if ($hospitalIds->isEmpty()) {
            return [
                ['label' => 'Low stock items', 'value' => 0, 'url' => '/supply'],
                ['label' => 'Damaged/expiring batches', 'value' => 0, 'url' => '/supply'],
            ];
        }

        $lowStock = $this->lowStockForHospitals($hospitalIds)->count();
        $batchIssues = SupplyBatch::whereIn('hospital_id', $hospitalIds)
            ->where(function ($query): void {
                $query->where('is_damaged', true)
                    ->orWhere(function ($expiring): void {
                        $expiring->where('is_damaged', false)
                            ->whereNotNull('expires_on')
                            ->where('expires_on', '<=', now()->addDays(30)->toDateString());
                    });
            })
            ->count();

        return [
            ['label' => 'Low stock items', 'value' => $lowStock, 'url' => '/supply?low_stock=1'],
            ['label' => 'Damaged/expiring batches', 'value' => $batchIssues, 'url' => '/supply'],
        ];
    }

    private function lowStockRows(int $hospitalId): Collection
    {
        return $this->lowStockForHospitals(collect([$hospitalId]));
    }

    private function lowStockForHospitals(Collection $hospitalIds): Collection
    {
        return SupplyStockBalance::query()
            ->join('supply_reorder_rules', function ($join): void {
                $join->on('supply_stock_balances.item_id', '=', 'supply_reorder_rules.item_id')
                    ->on('supply_stock_balances.hospital_id', '=', 'supply_reorder_rules.hospital_id')
                    ->where('supply_reorder_rules.is_active', true);
            })
            ->whereIn('supply_stock_balances.hospital_id', $hospitalIds->all())
            ->whereColumn('supply_stock_balances.quantity_on_hand', '<', 'supply_reorder_rules.minimum_quantity')
            ->select('supply_stock_balances.*')
            ->with('item')
            ->get();
    }

    private function recipientsFor(int $hospitalId): Collection
    {
        return User::all()->filter(fn (User $user): bool => $user->can('use supply') && $user->canAccessHospital($hospitalId));
    }

    private function hospitalIdsFor(User $user, bool $includeAll): Collection
    {
        if ($includeAll || $user->hasRole('SuperAdmin')) {
            return Hospital::active()->pluck('id');
        }

        return collect([$user->current_hospital_id])->filter();
    }

    private function notify(User $user, int $hospitalId, string $type, string $title, string $url): void
    {
        SuiteNotification::firstOrCreate([
            'notifiable_type' => $user->getMorphClass(),
            'notifiable_id' => $user->id,
            'module' => 'supply',
            'type' => $type,
            'title' => $title,
            'url' => $url,
            'hospital_id' => $hospitalId,
        ], [
            'read_at' => null,
        ]);
    }
}

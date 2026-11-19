<?php

namespace App\Console\Commands;

use App\Models\Hospital;
use App\Modules\Supply\Services\SupplyAlertService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('supply:refresh-alerts')]
#[Description('Create low-stock, damaged, and expiring supply notifications')]
class RefreshSupplyAlerts extends Command
{
    public function __construct(public SupplyAlertService $alerts)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $issues = 0;

        foreach (Hospital::active()->pluck('id') as $hospitalId) {
            $result = $this->alerts->refresh((int) $hospitalId);
            $issues += $result['low_stock'] + $result['damaged'] + $result['expiring'];
        }

        $this->info("Refreshed supply alerts; {$issues} issues found.");

        return self::SUCCESS;
    }
}

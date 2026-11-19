<?php

namespace App\Console\Commands;

use App\Modules\Maintenance\Services\MaintenanceAlertService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('maintenance:flag-overdue')]
#[Description('Flag overdue maintenance work orders and notify responsible users')]
class FlagOverdueWorkOrders extends Command
{
    public function __construct(public MaintenanceAlertService $alerts)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $flagged = $this->alerts->refresh();

        $this->info("Checked overdue work orders: {$flagged}.");

        return self::SUCCESS;
    }
}

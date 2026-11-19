<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Modules\Maintenance\Models\MaintPreventiveSchedule;
use App\Modules\Maintenance\Services\WorkOrderService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('maintenance:generate-preventive')]
#[Description('Generate work orders from due preventive maintenance schedules')]
class GeneratePreventiveWorkOrders extends Command
{
    public function __construct(public WorkOrderService $workOrders)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $actor = User::role('SuperAdmin')->first();

        if ($actor === null) {
            $this->error('No SuperAdmin user is available to generate work orders.');

            return self::FAILURE;
        }

        $generated = 0;

        foreach (MaintPreventiveSchedule::due()->get() as $schedule) {
            $this->workOrders->generatePreventive($schedule, $actor);
            $generated++;
        }

        $this->info("Generated {$generated} preventive work orders.");

        return self::SUCCESS;
    }
}

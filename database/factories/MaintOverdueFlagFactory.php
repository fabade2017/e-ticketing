<?php

namespace Database\Factories;

use App\Modules\Maintenance\Models\MaintOverdueFlag;
use App\Modules\Maintenance\Models\MaintWorkOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MaintOverdueFlag> */
class MaintOverdueFlagFactory extends Factory
{
    protected $model = MaintOverdueFlag::class;

    public function definition(): array
    {
        return [
            'work_order_id' => MaintWorkOrder::factory(),
            'flagged_at' => now(),
        ];
    }
}

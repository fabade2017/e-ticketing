<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Maintenance\Models\MaintWorkOrder;
use App\Modules\Maintenance\Models\MaintWorkOrderPart;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MaintWorkOrderPart> */
class MaintWorkOrderPartFactory extends Factory
{
    protected $model = MaintWorkOrderPart::class;

    public function definition(): array
    {
        return [
            'work_order_id' => MaintWorkOrder::factory(),
            'quantity' => fake()->numberBetween(1, 10),
            'recorded_by' => User::factory(),
        ];
    }
}

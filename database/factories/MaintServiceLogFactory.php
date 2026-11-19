<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Maintenance\Models\MaintServiceLog;
use App\Modules\Maintenance\Models\MaintWorkOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MaintServiceLog> */
class MaintServiceLogFactory extends Factory
{
    protected $model = MaintServiceLog::class;

    public function definition(): array
    {
        return [
            'work_order_id' => MaintWorkOrder::factory(),
            'user_id' => User::factory(),
            'action' => fake()->randomElement(['opened', 'assigned', 'in_progress', 'completed']),
            'note' => fake()->sentence(),
            'metadata' => [],
            'created_at' => now(),
        ];
    }
}

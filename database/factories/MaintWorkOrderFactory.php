<?php

namespace Database\Factories;

use App\Models\Hospital;
use App\Models\User;
use App\Modules\Maintenance\Models\Equipment;
use App\Modules\Maintenance\Models\MaintWorkOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MaintWorkOrder> */
class MaintWorkOrderFactory extends Factory
{
    protected $model = MaintWorkOrder::class;

    public function definition(): array
    {
        return [
            'work_order_number' => fake()->unique()->numerify('MWO-#####'),
            'hospital_id' => Hospital::factory(),
            'equipment_id' => Equipment::factory(),
            'fault_code' => fake()->numerify('FLT-###'),
            'title' => fake()->sentence(3),
            'description' => fake()->sentence(),
            'priority' => fake()->randomElement(['low', 'medium', 'high']),
            'status' => 'pending',
            'requested_by' => User::factory(),
            'due_at' => now()->addDays(3),
        ];
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes): array => ['due_at' => now()->subDay()]);
    }
}

<?php

namespace Database\Factories;

use App\Models\Hospital;
use App\Modules\Maintenance\Models\Equipment;
use App\Modules\Maintenance\Models\MaintPreventiveSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MaintPreventiveSchedule> */
class MaintPreventiveScheduleFactory extends Factory
{
    protected $model = MaintPreventiveSchedule::class;

    public function definition(): array
    {
        return [
            'hospital_id' => Hospital::factory(),
            'equipment_id' => Equipment::factory(),
            'title' => fake()->sentence(3),
            'interval_days' => 30,
            'next_due_at' => now()->addDays(30),
            'is_active' => true,
        ];
    }

    public function due(): static
    {
        return $this->state(fn (array $attributes): array => ['next_due_at' => now()->subDay()]);
    }
}

<?php

namespace Database\Factories;

use App\Models\Hospital;
use App\Models\User;
use App\Modules\Maintenance\Models\Equipment;
use App\Modules\Maintenance\Models\Fault;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Fault> */
class FaultFactory extends Factory
{
    protected $model = Fault::class;

    public function definition(): array
    {
        return [
            'hospital_id' => Hospital::factory(),
            'equipment_id' => Equipment::factory(),
            'code' => fake()->unique()->numerify('FLT-###'),
            'title' => fake()->sentence(3),
            'description' => fake()->sentence(),
            'reported_by' => User::factory(),
            'status' => 'open',
        ];
    }
}

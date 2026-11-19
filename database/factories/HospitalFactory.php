<?php

namespace Database\Factories;

use App\Models\Hospital;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Hospital>
 */
class HospitalFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company().' Hospital',
            'code' => fake()->unique()->bothify('HSP-####'),
            'address' => fake()->address(),
            'state' => 'Lagos',
            'is_active' => true,
        ];
    }
}

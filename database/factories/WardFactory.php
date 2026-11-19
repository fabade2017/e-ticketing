<?php

namespace Database\Factories;

use App\Models\Hospital;
use App\Modules\Care\Models\Ward;
use Illuminate\Database\Eloquent\Factories\Factory;

class WardFactory extends Factory
{
    protected $model = Ward::class;

    public function definition(): array
    {
        return [
            'hospital_id' => Hospital::factory(),
            'code' => fake()->unique()->bothify('W-?#'),
            'name' => fake()->randomElement(['Medical', 'Surgical', 'Paediatric']).' Ward',
            'department' => 'Clinical',
            'capacity' => fake()->numberBetween(4, 40),
            'is_active' => true,
        ];
    }
}

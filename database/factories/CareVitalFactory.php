<?php

namespace Database\Factories;

use App\Models\Hospital;
use App\Models\User;
use App\Modules\Care\Models\CareVisit;
use App\Modules\Care\Models\CareVital;
use Illuminate\Database\Eloquent\Factories\Factory;

class CareVitalFactory extends Factory
{
    protected $model = CareVital::class;

    public function definition(): array
    {
        return [
            'visit_id' => CareVisit::factory(),
            'hospital_id' => Hospital::factory(),
            'recorded_by' => User::factory(),
            'height_cm' => fake()->numberBetween(150, 200),
            'weight_kg' => fake()->numberBetween(50, 100),
            'temperature_c' => fake()->randomFloat(1, 36, 38),
            'pulse' => fake()->numberBetween(60, 100),
            'bp_systolic' => fake()->numberBetween(100, 140),
            'bp_diastolic' => fake()->numberBetween(60, 90),
            'recorded_at' => now(),
        ];
    }
}

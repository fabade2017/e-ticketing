<?php

namespace Database\Factories;

use App\Models\Hospital;
use App\Modules\Care\Models\Bed;
use App\Modules\Care\Models\Ward;
use Illuminate\Database\Eloquent\Factories\Factory;

class BedFactory extends Factory
{
    protected $model = Bed::class;

    public function definition(): array
    {
        return [
            'hospital_id' => Hospital::factory(),
            'ward_id' => Ward::factory(),
            'code' => fake()->unique()->numerify('B-###'),
            'status' => 'available',
            'current_patient_id' => null,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Hospital;
use App\Models\User;
use App\Modules\Care\Models\CareFrontlineRecord;
use App\Modules\Care\Models\CarePatient;
use Illuminate\Database\Eloquent\Factories\Factory;

class CareFrontlineRecordFactory extends Factory
{
    protected $model = CareFrontlineRecord::class;

    public function definition(): array
    {
        return [
            'hospital_id' => Hospital::factory(),
            'patient_id' => CarePatient::factory(),
            'created_by' => User::factory(),
            'deployment_unit' => fake()->word().' Battalion',
            'theatre' => fake()->randomElement(['North East', 'North West', 'South South']),
            'duty_type' => 'operational',
            'start_date' => now()->subMonths(6)->toDateString(),
            'end_date' => now()->subMonth()->toDateString(),
            'metadata' => [],
        ];
    }
}

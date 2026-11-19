<?php

namespace Database\Factories;

use App\Models\Hospital;
use App\Models\User;
use App\Modules\Care\Models\CareDiagnosis;
use App\Modules\Care\Models\CarePatient;
use App\Modules\Care\Models\CareVisit;
use Illuminate\Database\Eloquent\Factories\Factory;

class CareDiagnosisFactory extends Factory
{
    protected $model = CareDiagnosis::class;

    public function definition(): array
    {
        return [
            'visit_id' => CareVisit::factory(),
            'hospital_id' => Hospital::factory(),
            'patient_id' => CarePatient::factory(),
            'diagnosed_by' => User::factory(),
            'code' => fake()->bothify('???-##'),
            'description' => fake()->sentence(),
            'type' => 'final',
            'is_active' => true,
        ];
    }
}

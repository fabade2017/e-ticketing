<?php

namespace Database\Factories;

use App\Models\Hospital;
use App\Models\User;
use App\Modules\Care\Models\CarePatient;
use App\Modules\Care\Models\CarePrescription;
use App\Modules\Care\Models\CareVisit;
use Illuminate\Database\Eloquent\Factories\Factory;

class CarePrescriptionFactory extends Factory
{
    protected $model = CarePrescription::class;

    public function definition(): array
    {
        return [
            'visit_id' => CareVisit::factory(),
            'hospital_id' => Hospital::factory(),
            'patient_id' => CarePatient::factory(),
            'prescribed_by' => User::factory(),
            'medication' => fake()->randomElement(['Paracetamol', 'Amoxicillin', 'Artesunate']),
            'dosage' => '1 tablet',
            'frequency' => 'twice daily',
            'duration_days' => 5,
            'quantity' => 10,
            'instructions' => fake()->sentence(),
            'status' => 'active',
        ];
    }
}

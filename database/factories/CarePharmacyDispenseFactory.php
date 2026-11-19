<?php

namespace Database\Factories;

use App\Models\Hospital;
use App\Models\User;
use App\Modules\Care\Models\CarePatient;
use App\Modules\Care\Models\CarePharmacyDispense;
use App\Modules\Care\Models\CarePrescription;
use Illuminate\Database\Eloquent\Factories\Factory;

class CarePharmacyDispenseFactory extends Factory
{
    protected $model = CarePharmacyDispense::class;

    public function definition(): array
    {
        return [
            'prescription_id' => CarePrescription::factory(),
            'hospital_id' => Hospital::factory(),
            'patient_id' => CarePatient::factory(),
            'dispensed_by' => User::factory(),
            'quantity' => fake()->numberBetween(1, 10),
            'notes' => fake()->sentence(),
            'dispensed_at' => now(),
        ];
    }
}

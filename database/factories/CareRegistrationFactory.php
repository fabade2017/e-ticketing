<?php

namespace Database\Factories;

use App\Models\Hospital;
use App\Modules\Care\Models\CarePatient;
use App\Modules\Care\Models\CareRegistration;
use Illuminate\Database\Eloquent\Factories\Factory;

class CareRegistrationFactory extends Factory
{
    protected $model = CareRegistration::class;

    public function definition(): array
    {
        return [
            'registration_number' => fake()->unique()->numerify('REG-#####'),
            'patient_id' => CarePatient::factory(),
            'hospital_id' => Hospital::factory(),
            'sponsor_id' => null,
            'category' => 'civilian',
            'status' => 'pending',
            'fee_amount' => 10000,
            'waiver_amount' => 0,
            'payable_amount' => 10000,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'approved',
            'identity_verified' => true,
            'evidence_verified' => true,
            'sponsor_approved' => true,
            'reviewed_at' => now(),
        ]);
    }
}

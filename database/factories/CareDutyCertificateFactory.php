<?php

namespace Database\Factories;

use App\Models\Hospital;
use App\Models\User;
use App\Modules\Care\Models\CareDutyCertificate;
use App\Modules\Care\Models\CarePatient;
use Illuminate\Database\Eloquent\Factories\Factory;

class CareDutyCertificateFactory extends Factory
{
    protected $model = CareDutyCertificate::class;

    public function definition(): array
    {
        return [
            'certificate_number' => fake()->unique()->numerify('DUTY-#####'),
            'hospital_id' => Hospital::factory(),
            'patient_id' => CarePatient::factory(),
            'status' => 'FIT',
            'statement' => 'Fit for duty.',
            'review_date' => now()->addYear()->toDateString(),
            'issued_by' => User::factory(),
            'issued_at' => now(),
            'is_current' => true,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Hospital;
use App\Models\User;
use App\Modules\Care\Models\CareAppointment;
use App\Modules\Care\Models\CarePatient;
use Illuminate\Database\Eloquent\Factories\Factory;

class CareAppointmentFactory extends Factory
{
    protected $model = CareAppointment::class;

    public function definition(): array
    {
        return [
            'appointment_number' => fake()->unique()->numerify('APT-#####'),
            'hospital_id' => Hospital::factory(),
            'patient_id' => CarePatient::factory(),
            'requested_by' => User::factory(),
            'requested_for' => now()->addDay(),
            'reason' => fake()->sentence(),
            'status' => 'requested',
        ];
    }

    public function confirmed(User $reception): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'confirmed',
            'confirmed_by' => $reception->id,
            'confirmed_at' => now(),
        ]);
    }
}

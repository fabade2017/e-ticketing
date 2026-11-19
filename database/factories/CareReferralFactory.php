<?php

namespace Database\Factories;

use App\Models\Hospital;
use App\Models\User;
use App\Modules\Care\Models\CarePatient;
use App\Modules\Care\Models\CareReferral;
use Illuminate\Database\Eloquent\Factories\Factory;

class CareReferralFactory extends Factory
{
    protected $model = CareReferral::class;

    public function definition(): array
    {
        return [
            'referral_number' => fake()->unique()->numerify('REF-#####'),
            'from_hospital_id' => Hospital::factory(),
            'to_hospital_id' => Hospital::factory(),
            'patient_id' => CarePatient::factory(),
            'requested_by' => User::factory(),
            'reason' => fake()->sentence(),
            'status' => 'pending',
        ];
    }

    public function accepted(User $accepter): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'accepted',
            'accepted_by' => $accepter->id,
            'accepted_at' => now(),
        ]);
    }
}

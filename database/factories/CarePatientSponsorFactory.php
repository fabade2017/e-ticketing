<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Care\Models\CarePatient;
use App\Modules\Care\Models\CarePatientSponsor;
use Illuminate\Database\Eloquent\Factories\Factory;

class CarePatientSponsorFactory extends Factory
{
    protected $model = CarePatientSponsor::class;

    public function definition(): array
    {
        return [
            'patient_id' => CarePatient::factory(),
            'sponsor_user_id' => User::factory(),
            'sponsor_patient_id' => null,
            'relationship' => 'sponsor',
            'is_approved' => false,
        ];
    }

    public function approved(User $approver): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_approved' => true,
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);
    }
}

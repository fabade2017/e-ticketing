<?php

namespace Database\Factories;

use App\Models\Hospital;
use App\Models\User;
use App\Modules\Care\Models\CareLabOrder;
use App\Modules\Care\Models\CarePatient;
use App\Modules\Care\Models\CareVisit;
use Illuminate\Database\Eloquent\Factories\Factory;

class CareLabOrderFactory extends Factory
{
    protected $model = CareLabOrder::class;

    public function definition(): array
    {
        return [
            'order_number' => fake()->unique()->numerify('LAB-#####'),
            'visit_id' => CareVisit::factory(),
            'hospital_id' => Hospital::factory(),
            'patient_id' => CarePatient::factory(),
            'ordered_by' => User::factory(),
            'test_code' => fake()->bothify('TST-##'),
            'test_name' => fake()->randomElement(['Full blood count', 'Malaria parasite', 'Urinalysis']),
            'priority' => 'routine',
            'status' => 'ordered',
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }
}

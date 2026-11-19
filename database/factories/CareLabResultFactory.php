<?php

namespace Database\Factories;

use App\Models\Hospital;
use App\Models\User;
use App\Modules\Care\Models\CareLabOrder;
use App\Modules\Care\Models\CareLabResult;
use App\Modules\Care\Models\CarePatient;
use Illuminate\Database\Eloquent\Factories\Factory;

class CareLabResultFactory extends Factory
{
    protected $model = CareLabResult::class;

    public function definition(): array
    {
        return [
            'lab_order_id' => CareLabOrder::factory(),
            'hospital_id' => Hospital::factory(),
            'patient_id' => CarePatient::factory(),
            'result_by' => User::factory(),
            'findings' => fake()->paragraph(),
            'status' => 'draft',
        ];
    }

    public function published(User $publisher): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'published',
            'published_by' => $publisher->id,
            'published_at' => now(),
        ]);
    }
}

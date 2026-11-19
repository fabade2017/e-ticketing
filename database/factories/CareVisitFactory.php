<?php

namespace Database\Factories;

use App\Models\Hospital;
use App\Models\User;
use App\Modules\Care\Models\CarePatient;
use App\Modules\Care\Models\CareVisit;
use Illuminate\Database\Eloquent\Factories\Factory;

class CareVisitFactory extends Factory
{
    protected $model = CareVisit::class;

    public function definition(): array
    {
        return [
            'visit_number' => fake()->unique()->numerify('VIS-#####'),
            'hospital_id' => Hospital::factory(),
            'patient_id' => CarePatient::factory(),
            'visit_type' => 'consultation',
            'status' => 'open',
            'checked_in_at' => now(),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function published(string $summary, User $clinician): static
    {
        return $this->state(fn (array $attributes): array => [
            'published_summary' => $summary,
            'internal_notes' => 'Private clinical note.',
            'clinician_id' => $clinician->id,
        ]);
    }
}

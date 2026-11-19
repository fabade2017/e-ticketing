<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Care\Models\CareMonthlyReport;
use Illuminate\Database\Eloquent\Factories\Factory;

class CareMonthlyReportFactory extends Factory
{
    protected $model = CareMonthlyReport::class;

    public function definition(): array
    {
        return [
            'hospital_id' => null,
            'scope' => 'network',
            'scope_key' => 'network:'.now()->format('Y-m-01'),
            'report_month' => now()->startOfMonth()->toDateString(),
            'distinct_patients' => fake()->numberBetween(1, 100),
            'completed_visits' => fake()->numberBetween(1, 200),
            'patients_tested' => fake()->numberBetween(0, 80),
            'tests_performed' => fake()->numberBetween(0, 120),
            'disease_encounters' => fake()->numberBetween(0, 150),
            'generated_by' => User::factory(),
            'generated_at' => now(),
            'metadata' => [],
        ];
    }
}

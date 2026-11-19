<?php

namespace Database\Factories;

use App\Models\Hospital;
use App\Models\User;
use App\Modules\Care\Models\CareDocument;
use App\Modules\Care\Models\CarePatient;
use Illuminate\Database\Eloquent\Factories\Factory;

class CareDocumentFactory extends Factory
{
    protected $model = CareDocument::class;

    public function definition(): array
    {
        return [
            'hospital_id' => Hospital::factory(),
            'patient_id' => CarePatient::factory(),
            'registration_id' => null,
            'document_type' => 'identity',
            'path' => fake()->filePath(),
            'original_name' => fake()->word().'.jpg',
            'mime_type' => 'image/jpeg',
            'size' => fake()->numberBetween(1000, 500000),
            'uploaded_by' => User::factory(),
            'is_verified' => false,
        ];
    }
}

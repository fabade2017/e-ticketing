<?php

namespace Database\Factories;

use App\Models\Hospital;
use App\Models\User;
use App\Modules\Care\Models\CareLabImage;
use App\Modules\Care\Models\CareLabResult;
use Illuminate\Database\Eloquent\Factories\Factory;

class CareLabImageFactory extends Factory
{
    protected $model = CareLabImage::class;

    public function definition(): array
    {
        return [
            'lab_result_id' => CareLabResult::factory(),
            'hospital_id' => Hospital::factory(),
            'uploaded_by' => User::factory(),
            'path' => fake()->filePath().'.jpg',
            'original_name' => fake()->word().'.jpg',
            'mime_type' => 'image/jpeg',
            'size' => fake()->numberBetween(1000, 2000000),
        ];
    }
}

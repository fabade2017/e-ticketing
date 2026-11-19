<?php

namespace Database\Factories;

use App\Models\Hospital;
use App\Models\User;
use App\Modules\Care\Models\Bed;
use App\Modules\Care\Models\CareAdmission;
use App\Modules\Care\Models\CarePatient;
use App\Modules\Care\Models\Ward;
use Illuminate\Database\Eloquent\Factories\Factory;

class CareAdmissionFactory extends Factory
{
    protected $model = CareAdmission::class;

    public function definition(): array
    {
        return [
            'admission_number' => fake()->unique()->numerify('ADM-#####'),
            'hospital_id' => Hospital::factory(),
            'patient_id' => CarePatient::factory(),
            'ward_id' => Ward::factory(),
            'bed_id' => Bed::factory(),
            'admitted_by' => User::factory(),
            'status' => 'admitted',
            'diagnosis' => fake()->sentence(),
            'admitted_at' => now(),
        ];
    }
}

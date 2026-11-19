<?php

namespace Database\Factories;

use App\Models\Hospital;
use App\Models\User;
use App\Modules\Care\Models\CarePatient;
use Illuminate\Database\Eloquent\Factories\Factory;

class CarePatientFactory extends Factory
{
    protected $model = CarePatient::class;

    public function definition(): array
    {
        return [
            'network_number' => fake()->unique()->numerify('NET-#####'),
            'army_number' => null,
            'category' => fake()->randomElement(['military', 'civilian', 'dependant']),
            'hospital_id' => Hospital::factory(),
            'linked_user_id' => null,
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'date_of_birth' => fake()->dateTimeBetween('-60 years', '-20 years')->format('Y-m-d'),
            'gender' => fake()->randomElement(['male', 'female']),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'is_active' => true,
        ];
    }

    public function linkedTo(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'linked_user_id' => $user->id,
            'hospital_id' => $user->current_hospital_id ?? $attributes['hospital_id'],
        ]);
    }
}

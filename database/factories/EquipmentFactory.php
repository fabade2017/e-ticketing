<?php

namespace Database\Factories;

use App\Models\Hospital;
use App\Modules\Maintenance\Models\Equipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Equipment> */
class EquipmentFactory extends Factory
{
    protected $model = Equipment::class;

    public function definition(): array
    {
        return [
            'hospital_id' => Hospital::factory(),
            'asset_id' => fake()->unique()->numerify('ASSET-#####'),
            'name' => fake()->words(2, true),
            'unit' => fake()->randomElement(['Motor Transport', 'Signals', 'Engineering']),
            'model' => fake()->word(),
            'location' => fake()->city(),
            'is_active' => true,
        ];
    }
}

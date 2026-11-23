<?php

namespace Database\Factories;

use App\Modules\Supply\Models\SupplyItemCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SupplyItemCategory> */
class SupplyItemCategoryFactory extends Factory
{
    protected $model = SupplyItemCategory::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'code' => strtoupper(fake()->unique()->bothify('???')),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}

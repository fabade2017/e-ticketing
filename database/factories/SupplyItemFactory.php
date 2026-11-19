<?php

namespace Database\Factories;

use App\Modules\Supply\Models\SupplyItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SupplyItem> */
class SupplyItemFactory extends Factory
{
    protected $model = SupplyItem::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'sku' => fake()->unique()->numerify('SKU-#####'),
            'category' => fake()->randomElement(['medical', 'logistics', 'uniform', 'fuel']),
            'unit' => fake()->randomElement(['each', 'box', 'litre', 'carton']),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}

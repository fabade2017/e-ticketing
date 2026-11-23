<?php

namespace Database\Factories;

use App\Modules\Supply\Models\SupplyItem;
use App\Modules\Supply\Models\SupplyItemCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SupplyItem> */
class SupplyItemFactory extends Factory
{
    protected $model = SupplyItem::class;

    public function definition(): array
    {
        $category = SupplyItemCategory::active()->inRandomOrder()->first()
            ?? SupplyItemCategory::factory()->create();

        return [
            'name' => fake()->words(2, true),
            'sku' => fake()->unique()->numerify('SKU-#####'),
            'category' => $category->name,
            'supply_item_category_id' => $category->id,
            'unit' => fake()->randomElement(['each', 'box', 'litre', 'carton']),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}

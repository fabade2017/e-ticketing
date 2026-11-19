<?php

namespace Database\Factories;

use App\Models\Hospital;
use App\Modules\Supply\Models\SupplyItem;
use App\Modules\Supply\Models\SupplyStockBalance;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SupplyStockBalance> */
class SupplyStockBalanceFactory extends Factory
{
    protected $model = SupplyStockBalance::class;

    public function definition(): array
    {
        return [
            'item_id' => SupplyItem::factory(),
            'hospital_id' => Hospital::factory(),
            'store' => 'main',
            'quantity_on_hand' => fake()->numberBetween(10, 100),
            'quantity_reserved' => 0,
        ];
    }
}

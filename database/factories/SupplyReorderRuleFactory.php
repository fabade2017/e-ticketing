<?php

namespace Database\Factories;

use App\Models\Hospital;
use App\Modules\Supply\Models\SupplyItem;
use App\Modules\Supply\Models\SupplyReorderRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SupplyReorderRule> */
class SupplyReorderRuleFactory extends Factory
{
    protected $model = SupplyReorderRule::class;

    public function definition(): array
    {
        return [
            'hospital_id' => Hospital::factory(),
            'item_id' => SupplyItem::factory(),
            'minimum_quantity' => 10,
            'target_quantity' => 50,
            'is_active' => true,
        ];
    }
}

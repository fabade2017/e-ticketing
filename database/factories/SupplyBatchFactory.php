<?php

namespace Database\Factories;

use App\Models\Hospital;
use App\Modules\Supply\Models\SupplyBatch;
use App\Modules\Supply\Models\SupplyItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SupplyBatch> */
class SupplyBatchFactory extends Factory
{
    protected $model = SupplyBatch::class;

    public function definition(): array
    {
        return [
            'item_id' => SupplyItem::factory(),
            'hospital_id' => Hospital::factory(),
            'batch_code' => fake()->unique()->numerify('BATCH-####'),
            'expires_on' => now()->addMonths(6)->toDateString(),
            'is_damaged' => false,
            'quantity' => fake()->numberBetween(10, 100),
        ];
    }
}

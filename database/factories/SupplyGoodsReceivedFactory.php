<?php

namespace Database\Factories;

use App\Models\Hospital;
use App\Models\User;
use App\Modules\Supply\Models\SupplyGoodsReceived;
use App\Modules\Supply\Models\SupplyItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SupplyGoodsReceived> */
class SupplyGoodsReceivedFactory extends Factory
{
    protected $model = SupplyGoodsReceived::class;

    public function definition(): array
    {
        return [
            'hospital_id' => Hospital::factory(),
            'item_id' => SupplyItem::factory(),
            'quantity' => fake()->numberBetween(1, 50),
            'supplier' => fake()->company(),
            'document_no' => fake()->unique()->numerify('GRN-####'),
            'idempotency_key' => fake()->unique()->uuid(),
            'status' => 'pending',
            'received_by' => User::factory(),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'approved',
            'approved_by' => User::factory(),
            'approved_at' => now(),
        ]);
    }
}

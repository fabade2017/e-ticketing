<?php

namespace Database\Factories;

use App\Models\Hospital;
use App\Models\User;
use App\Modules\Supply\Models\SupplyGoodsIssued;
use App\Modules\Supply\Models\SupplyItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SupplyGoodsIssued> */
class SupplyGoodsIssuedFactory extends Factory
{
    protected $model = SupplyGoodsIssued::class;

    public function definition(): array
    {
        return [
            'hospital_id' => Hospital::factory(),
            'item_id' => SupplyItem::factory(),
            'quantity' => fake()->numberBetween(1, 20),
            'recipient_type' => 'unit',
            'recipient_id' => fake()->randomDigitNotNull(),
            'purpose' => fake()->sentence(),
            'idempotency_key' => fake()->unique()->uuid(),
            'status' => 'pending',
            'issued_by' => User::factory(),
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

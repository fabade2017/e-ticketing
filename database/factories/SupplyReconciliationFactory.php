<?php

namespace Database\Factories;

use App\Models\Hospital;
use App\Models\User;
use App\Modules\Supply\Models\SupplyItem;
use App\Modules\Supply\Models\SupplyReconciliation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SupplyReconciliation> */
class SupplyReconciliationFactory extends Factory
{
    protected $model = SupplyReconciliation::class;

    public function definition(): array
    {
        return [
            'hospital_id' => Hospital::factory(),
            'item_id' => SupplyItem::factory(),
            'expected_quantity' => 100,
            'counted_quantity' => 98,
            'difference' => -2,
            'idempotency_key' => fake()->unique()->uuid(),
            'status' => 'pending',
            'notes' => fake()->sentence(),
            'requested_by' => User::factory(),
        ];
    }
}

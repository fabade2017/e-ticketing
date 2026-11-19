<?php

namespace Database\Factories;

use App\Models\Hospital;
use App\Models\User;
use App\Modules\Supply\Models\SupplyApprovalRequest;
use App\Modules\Supply\Models\SupplyGoodsIssued;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SupplyApprovalRequest> */
class SupplyApprovalRequestFactory extends Factory
{
    protected $model = SupplyApprovalRequest::class;

    public function definition(): array
    {
        return [
            'hospital_id' => Hospital::factory(),
            'subject_type' => (new SupplyGoodsIssued)->getMorphClass(),
            'subject_id' => SupplyGoodsIssued::factory(),
            'type' => 'stock.issue',
            'status' => 'pending',
            'requested_by' => User::factory(),
        ];
    }
}

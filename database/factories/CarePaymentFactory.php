<?php

namespace Database\Factories;

use App\Models\Hospital;
use App\Models\User;
use App\Modules\Care\Models\CareInvoice;
use App\Modules\Care\Models\CarePatient;
use App\Modules\Care\Models\CarePayment;
use Illuminate\Database\Eloquent\Factories\Factory;

class CarePaymentFactory extends Factory
{
    protected $model = CarePayment::class;

    public function definition(): array
    {
        return [
            'payment_reference' => fake()->unique()->numerify('PAY-#####'),
            'invoice_id' => CareInvoice::factory(),
            'hospital_id' => Hospital::factory(),
            'patient_id' => CarePatient::factory(),
            'amount' => 10000,
            'method' => 'manual_transfer',
            'bank_reference' => null,
            'receipt_path' => null,
            'credited' => false,
            'status' => 'pending',
            'submitted_by' => User::factory(),
            'paid_at' => now(),
            'metadata' => [],
        ];
    }

    public function verified(User $verifier): static
    {
        return $this->state(fn (array $attributes): array => [
            'credited' => true,
            'status' => 'verified',
            'bank_reference' => 'BANK-'.fake()->unique()->numerify('#####'),
            'verified_by' => $verifier->id,
            'verified_at' => now(),
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Models\Hospital;
use App\Models\User;
use App\Modules\Care\Models\CareInvoice;
use App\Modules\Care\Models\CarePatient;
use Illuminate\Database\Eloquent\Factories\Factory;

class CareInvoiceFactory extends Factory
{
    protected $model = CareInvoice::class;

    public function definition(): array
    {
        return [
            'invoice_number' => fake()->unique()->numerify('INV-#####'),
            'hospital_id' => Hospital::factory(),
            'patient_id' => CarePatient::factory(),
            'currency' => 'NGN',
            'subtotal' => 10000,
            'discount' => 0,
            'total' => 10000,
            'amount_paid' => 0,
            'status' => 'unpaid',
            'issued_by' => User::factory(),
            'due_at' => now()->addDays(7),
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes): array => [
            'amount_paid' => $attributes['total'],
            'status' => 'paid',
        ]);
    }
}

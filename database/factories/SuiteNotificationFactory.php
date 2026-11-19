<?php

namespace Database\Factories;

use App\Models\SuiteNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SuiteNotification>
 */
class SuiteNotificationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'notifiable_type' => (new User)->getMorphClass(),
            'notifiable_id' => User::factory(),
            'module' => 'ticketing',
            'type' => 'ticket.updated',
            'title' => fake()->sentence(),
            'url' => '/tickets',
            'read_at' => null,
        ];
    }

    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => now(),
        ]);
    }
}

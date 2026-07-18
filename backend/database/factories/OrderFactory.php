<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\Edition;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Order> */
class OrderFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'edition_id' => Edition::factory(),
            'type' => OrderType::PickPurchase,
            'amount_cents' => 499,
            'currency' => 'BRL',
            'status' => OrderStatus::Pending,
            'payment_provider' => 'mercadopago',
            'provider_reference' => null,
            'checkout_idempotency_key' => fake()->uuid(),
            'checkout_expires_at' => now()->addMinutes(30),
            'paid_at' => null,
            'metadata' => [],
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (): array => [
            'status' => OrderStatus::Paid,
            'provider_reference' => (string) fake()->unique()->numberBetween(100000, 999999),
            'paid_at' => now(),
        ]);
    }

    public function refunded(): static
    {
        return $this->paid()->state(fn (): array => ['status' => OrderStatus::Refunded]);
    }
}

<?php

namespace Database\Factories;

use App\Models\PaymentWebhookEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PaymentWebhookEvent> */
class PaymentWebhookEventFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'payment_provider' => 'mercadopago',
            'provider_event_id' => (string) fake()->unique()->numberBetween(100000, 999999),
            'resource_id' => (string) fake()->numberBetween(100000, 999999),
            'payload' => [],
            'processed_at' => null,
        ];
    }
}

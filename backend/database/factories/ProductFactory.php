<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $externalId = 'MLB'.fake()->unique()->numerify('##########');

        return [
            'provider' => 'mercadolivre',
            'external_id' => $externalId,
            'title' => fake()->words(4, true),
            'url' => "https://produto.mercadolivre.com.br/{$externalId}",
            'affiliate_url' => null,
            'price_cents' => fake()->numberBetween(1000, 100000),
            'currency' => 'BRL',
            'image_url' => fake()->imageUrl(),
            'raw' => ['id' => $externalId],
            'fetched_at' => now(),
        ];
    }
}

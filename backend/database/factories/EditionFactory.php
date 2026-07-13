<?php

namespace Database\Factories;

use App\Enums\EditionStatus;
use App\Models\Edition;
use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Edition> */
class EditionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'group_id' => Group::factory(),
            'name' => 'Natal '.fake()->year(),
            'type' => 'classic',
            'status' => EditionStatus::Draft,
            'budget_cents' => fake()->optional()->numberBetween(5000, 50000),
            'currency' => 'BRL',
            'event_date' => fake()->optional()->dateTimeBetween('now', '+1 year'),
            'settings' => [],
            'created_by' => User::factory(),
        ];
    }

    public function open(): static
    {
        return $this->state(fn (): array => ['status' => EditionStatus::Open]);
    }

    public function drawn(): static
    {
        return $this->state(fn (): array => ['status' => EditionStatus::Drawn, 'drawn_at' => now()]);
    }

    public function revealed(): static
    {
        return $this->state(fn (): array => ['status' => EditionStatus::Revealed, 'drawn_at' => now(), 'revealed_at' => now()]);
    }
}

<?php

namespace Database\Factories;

use App\Enums\DrawConstraintSource;
use App\Enums\DrawConstraintType;
use App\Models\DrawConstraint;
use App\Models\Edition;
use App\Models\EditionParticipant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DrawConstraint>
 */
class DrawConstraintFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'edition_id' => Edition::factory(),
            'type' => DrawConstraintType::MustNotPair,
            'giver_edition_participant_id' => fn (array $attributes): int => EditionParticipant::factory()->create(['edition_id' => $attributes['edition_id']])->id,
            'receiver_edition_participant_id' => fn (array $attributes): int => EditionParticipant::factory()->create(['edition_id' => $attributes['edition_id']])->id,
            'source' => DrawConstraintSource::Admin,
            'created_by' => User::factory(),
        ];
    }
}

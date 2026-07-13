<?php

namespace Database\Factories;

use App\Models\Assignment;
use App\Models\Edition;
use App\Models\EditionParticipant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Assignment>
 */
class AssignmentFactory extends Factory
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
            'giver_edition_participant_id' => fn (array $attributes): int => EditionParticipant::factory()->create(['edition_id' => $attributes['edition_id']])->id,
            'receiver_edition_participant_id' => fn (array $attributes): int => EditionParticipant::factory()->create(['edition_id' => $attributes['edition_id']])->id,
        ];
    }
}

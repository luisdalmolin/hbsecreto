<?php

namespace Database\Factories;

use App\Models\Edition;
use App\Models\EditionParticipant;
use App\Models\GroupMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EditionParticipant> */
class EditionParticipantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'edition_id' => Edition::factory(),
            'group_id' => function (array $attributes): int {
                $editionId = $attributes['edition_id'] ?? null;

                if (! is_int($editionId)) {
                    throw new \LogicException('An edition identifier is required.');
                }

                return Edition::query()->findOrFail($editionId)->group_id;
            },
            'group_member_id' => fn (array $attributes) => GroupMember::factory()->create(['group_id' => $attributes['group_id']])->id,
        ];
    }
}

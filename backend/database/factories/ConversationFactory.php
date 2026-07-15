<?php

namespace Database\Factories;

use App\Enums\ConversationType;
use App\Models\Assignment;
use App\Models\Conversation;
use App\Models\Edition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Conversation>
 */
class ConversationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'edition_id' => Edition::factory()->drawn(),
            'type' => ConversationType::Assignment,
            'assignment_id' => fn (array $attributes): int => Assignment::factory()->create([
                'edition_id' => $attributes['edition_id'],
            ])->id,
        ];
    }
}

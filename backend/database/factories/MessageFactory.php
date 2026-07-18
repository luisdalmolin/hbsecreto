<?php

namespace Database\Factories;

use App\Enums\ConversationType;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'edition_id' => function (array $attributes): int {
                $conversationId = $attributes['conversation_id'] ?? null;

                if (! is_int($conversationId)) {
                    throw new \LogicException('A conversation identifier is required.');
                }

                return Conversation::query()->findOrFail($conversationId)->edition_id;
            },
            'sender_edition_participant_id' => function (array $attributes): int {
                $conversationId = $attributes['conversation_id'] ?? null;

                if (! is_int($conversationId)) {
                    throw new \LogicException('A conversation identifier is required.');
                }

                $conversation = Conversation::query()->findOrFail($conversationId);

                return $conversation->type === ConversationType::Edition
                    ? $conversation->edition()->firstOrFail()->participants()->firstOrFail()->id
                    : $conversation->assignment()->firstOrFail()->giver_edition_participant_id;
            },
            'body' => fake()->sentence(),
        ];
    }
}

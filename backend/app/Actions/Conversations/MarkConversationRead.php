<?php

namespace App\Actions\Conversations;

use App\Models\Conversation;
use App\Models\ConversationRead;
use App\Models\EditionParticipant;

final class MarkConversationRead
{
    public function handle(Conversation $conversation, EditionParticipant $participant, ?int $messageId): ConversationRead
    {
        $conversation->assignment()
            ->where(function ($assignments) use ($participant): void {
                $assignments
                    ->where('giver_edition_participant_id', $participant->id)
                    ->orWhere('receiver_edition_participant_id', $participant->id);
            })
            ->firstOrFail();

        $timestamp = now();
        $lastReadAt = $messageId === null
            ? $timestamp
            : $conversation->messages()->whereKey($messageId)->firstOrFail()->created_at;

        if ($lastReadAt === null) {
            throw new \LogicException('A persisted message must have a creation timestamp.');
        }

        ConversationRead::query()->upsert(
            [[
                'conversation_id' => $conversation->id,
                'edition_participant_id' => $participant->id,
                'last_read_at' => $lastReadAt,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]],
            ['conversation_id', 'edition_participant_id'],
            ['last_read_at', 'updated_at'],
        );

        return ConversationRead::query()
            ->whereBelongsTo($conversation)
            ->whereBelongsTo($participant, 'editionParticipant')
            ->firstOrFail();
    }
}

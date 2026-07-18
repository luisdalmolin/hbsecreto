<?php

namespace App\Actions\Conversations;

use App\Enums\ConversationType;
use App\Models\Conversation;
use App\Models\ConversationRead;
use App\Models\EditionParticipant;
use Illuminate\Support\Facades\DB;

final class MarkConversationRead
{
    public function handle(Conversation $conversation, EditionParticipant $participant, ?int $messageId): ConversationRead
    {
        return DB::transaction(function () use ($conversation, $participant, $messageId): ConversationRead {
            $lockedConversation = Conversation::query()
                ->whereKey($conversation->id)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedConversation->edition()
                ->whereHas('participants', fn ($participants) => $participants->whereKey($participant->id))
                ->firstOrFail();

            if ($lockedConversation->type === ConversationType::Assignment) {
                $lockedConversation->assignment()
                    ->where(function ($assignments) use ($participant): void {
                        $assignments
                            ->where('giver_edition_participant_id', $participant->id)
                            ->orWhere('receiver_edition_participant_id', $participant->id);
                    })
                    ->firstOrFail();
            }

            $timestamp = now();
            $lastReadAt = $messageId === null
                ? $timestamp
                : $lockedConversation->messages()->whereKey($messageId)->firstOrFail()->created_at;

            if ($lastReadAt === null) {
                throw new \LogicException('A persisted message must have a creation timestamp.');
            }

            $read = ConversationRead::query()
                ->where('edition_id', $lockedConversation->edition_id)
                ->whereBelongsTo($lockedConversation)
                ->whereBelongsTo($participant, 'editionParticipant')
                ->lockForUpdate()
                ->first();

            if ($read === null) {
                return ConversationRead::query()->create([
                    'edition_id' => $lockedConversation->edition_id,
                    'conversation_id' => $lockedConversation->id,
                    'edition_participant_id' => $participant->id,
                    'last_read_at' => $lastReadAt,
                ]);
            }

            if ($read->last_read_at->lt($lastReadAt)) {
                $read->update(['last_read_at' => $lastReadAt]);
            }

            return $read->refresh();
        }, attempts: 3);
    }
}

<?php

namespace App\Actions\Conversations;

use App\Enums\EditionStatus;
use App\Exceptions\DomainConflictException;
use App\Models\Conversation;
use App\Models\Edition;
use App\Models\EditionParticipant;
use App\Models\Message;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class SendMessage
{
    public function handle(Conversation $conversation, EditionParticipant $sender, string $body): Message
    {
        return DB::transaction(function () use ($conversation, $sender, $body): Message {
            $edition = Edition::query()->whereKey($conversation->edition_id)->lockForUpdate()->firstOrFail();

            if (! in_array($edition->status, [EditionStatus::Drawn, EditionStatus::Revealed], true)) {
                throw new DomainConflictException(
                    $edition->status === EditionStatus::Archived ? 'chat.archived' : 'chat.unavailable',
                );
            }

            $lockedConversation = $edition->conversations()
                ->whereKey($conversation->id)
                ->whereHas('assignment', fn ($assignments) => $assignments
                    ->where('giver_edition_participant_id', $sender->id)
                    ->orWhere('receiver_edition_participant_id', $sender->id))
                ->lockForUpdate()
                ->firstOrFail();

            return $lockedConversation->messages()->create([
                'sender_edition_participant_id' => $sender->id,
                'body' => Str::of($body)->trim()->toString(),
            ]);
        }, attempts: 3);
    }
}

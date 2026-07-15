<?php

namespace App\Actions\Conversations;

use App\Enums\EditionStatus;
use App\Enums\GroupMemberStatus;
use App\Exceptions\DomainConflictException;
use App\Models\Assignment;
use App\Models\Conversation;
use App\Models\Edition;
use App\Models\EditionParticipant;
use App\Models\Message;
use App\Models\User;
use App\Notifications\ConversationMessageNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class SendMessage
{
    public function handle(Conversation $conversation, EditionParticipant $sender, string $body): Message
    {
        $message = DB::transaction(function () use ($conversation, $sender, $body): Message {
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

        /** @var Assignment $assignment */
        $assignment = $conversation->assignment()->firstOrFail();
        $recipientId = $assignment->giver_edition_participant_id === $sender->id
            ? $assignment->receiver_edition_participant_id
            : $assignment->giver_edition_participant_id;
        $recipient = EditionParticipant::query()
            ->whereKey($recipientId)
            ->whereHas('groupMember', fn ($members) => $members->where('status', GroupMemberStatus::Active))
            ->with('groupMember.user')
            ->first();
        $user = $recipient?->groupMember?->user;

        if ($user instanceof User) {
            $edition = Edition::query()->findOrFail($conversation->edition_id);
            $user->notify(new ConversationMessageNotification(
                groupId: $edition->group_id,
                editionId: $conversation->edition_id,
                conversationId: $conversation->id,
            ));
        }

        return $message;
    }
}

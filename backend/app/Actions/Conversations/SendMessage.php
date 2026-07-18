<?php

namespace App\Actions\Conversations;

use App\Enums\ConversationType;
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
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

final class SendMessage
{
    public function handle(Conversation $conversation, EditionParticipant $sender, string $body): Message
    {
        $message = DB::transaction(function () use ($conversation, $sender, $body): Message {
            $edition = Edition::query()->whereKey($conversation->edition_id)->lockForUpdate()->firstOrFail();
            $lockedConversation = $edition->conversations()
                ->whereKey($conversation->id)
                ->lockForUpdate()
                ->firstOrFail();
            $edition->participants()
                ->whereKey($sender->id)
                ->whereHas('groupMember', fn ($members) => $members->where('status', GroupMemberStatus::Active))
                ->firstOrFail();

            $availableStatuses = $lockedConversation->type === ConversationType::Edition
                ? [EditionStatus::Draft, EditionStatus::Open, EditionStatus::Drawn, EditionStatus::Revealed]
                : [EditionStatus::Drawn, EditionStatus::Revealed];

            if (! in_array($edition->status, $availableStatuses, true)) {
                throw new DomainConflictException(
                    $edition->status === EditionStatus::Archived ? 'chat.archived' : 'chat.unavailable',
                );
            }

            if ($lockedConversation->type === ConversationType::Assignment) {
                $lockedConversation->assignment()
                    ->where(function ($assignments) use ($sender): void {
                        $assignments
                            ->where('giver_edition_participant_id', $sender->id)
                            ->orWhere('receiver_edition_participant_id', $sender->id);
                    })
                    ->firstOrFail();
            }

            return $lockedConversation->messages()->create([
                'edition_id' => $edition->id,
                'sender_edition_participant_id' => $sender->id,
                'body' => Str::of($body)->trim()->toString(),
            ]);
        }, attempts: 3);

        if ($conversation->type === ConversationType::Edition) {
            $senderUserId = $sender->groupMember()->firstOrFail()->user_id;
            $edition = Edition::query()->findOrFail($conversation->edition_id);

            User::query()
                ->whereHas('groupMemberships', fn ($members) => $members
                    ->where('group_id', $edition->group_id)
                    ->where('status', GroupMemberStatus::Active)
                    ->whereHas('editionParticipants', fn ($participants) => $participants
                        ->where('edition_id', $edition->id)))
                ->when($senderUserId !== null, fn ($users) => $users->whereKeyNot($senderUserId))
                ->orderBy('id')
                ->chunkById(200, fn ($users) => Notification::send($users, new ConversationMessageNotification(
                    groupId: $edition->group_id,
                    editionId: $edition->id,
                    conversationId: $conversation->id,
                )));

            return $message;
        }

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

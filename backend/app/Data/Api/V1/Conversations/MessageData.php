<?php

namespace App\Data\Api\V1\Conversations;

use App\Enums\ConversationType;
use App\Enums\EditionStatus;
use App\Models\Assignment;
use App\Models\Conversation;
use App\Models\Edition;
use App\Models\EditionParticipant;
use App\Models\GroupMember;
use App\Models\Message;
use App\Models\User;
use OpenApi\Attributes as OA;
use Spatie\LaravelData\Resource;

#[OA\Schema(schema: 'Message', required: ['id', 'body', 'isMine', 'author', 'sentAt'])]
final class MessageData extends Resource
{
    public function __construct(
        #[OA\Property(example: 1)] public int $id,
        #[OA\Property(example: 'Qual tamanho você prefere?', maxLength: 1000)] public string $body,
        #[OA\Property(example: true)] public bool $isMine,
        #[OA\Property(ref: '#/components/schemas/MessageAuthor')] public MessageAuthorData $author,
        #[OA\Property(type: 'string', format: 'date-time')] public string $sentAt,
    ) {}

    public static function fromMessage(
        Message $message,
        Conversation $conversation,
        Edition $edition,
        EditionParticipant $participant,
    ): self {
        if ($message->created_at === null) {
            throw new \LogicException('A persisted message must have a creation timestamp.');
        }

        $sender = $message->sender;

        if ($sender === null) {
            throw new \LogicException('A persisted message must have a sender.');
        }

        $member = $sender->getRelation('groupMember');

        if (! $member instanceof GroupMember) {
            throw new \LogicException('A message sender must have a group member loaded.');
        }

        $anonymous = false;

        if ($conversation->type === ConversationType::Assignment) {
            $assignment = $conversation->getRelation('assignment');

            if (! $assignment instanceof Assignment) {
                throw new \LogicException('An assignment conversation must have its assignment loaded.');
            }

            $anonymous = $edition->status === EditionStatus::Drawn
                && $assignment->giver_edition_participant_id === $sender->id
                && $assignment->receiver_edition_participant_id === $participant->id;
        }

        return new self(
            id: $message->id,
            body: $message->body,
            isMine: $message->sender_edition_participant_id === $participant->id,
            author: new MessageAuthorData(
                displayName: $anonymous ? null : self::displayName($member),
                anonymous: $anonymous,
            ),
            sentAt: $message->created_at->toIso8601String(),
        );
    }

    private static function displayName(GroupMember $member): string
    {
        if ($member->display_name !== null) {
            return $member->display_name;
        }

        $user = $member->getRelation('user');

        if ($user instanceof User) {
            return $user->name;
        }

        $fallback = __('draw.participant');

        if (! is_string($fallback)) {
            throw new \LogicException('The participant fallback must be a string.');
        }

        return $fallback;
    }
}

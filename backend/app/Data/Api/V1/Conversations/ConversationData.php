<?php

namespace App\Data\Api\V1\Conversations;

use App\Enums\ConversationType;
use App\Enums\EditionStatus;
use App\Models\Assignment;
use App\Models\Conversation;
use App\Models\Edition;
use App\Models\EditionParticipant;
use OpenApi\Attributes as OA;
use Spatie\LaravelData\Resource;

#[OA\Schema(
    schema: 'Conversation',
    required: ['id', 'type', 'role', 'counterpart', 'unreadCount', 'lastMessageAt', 'canSend'],
    properties: [
        new OA\Property(property: 'counterpart', oneOf: [new OA\Schema(ref: '#/components/schemas/ConversationCounterpart'), new OA\Schema(type: 'null')]),
        new OA\Property(property: 'lastMessageAt', oneOf: [new OA\Schema(type: 'string', format: 'date-time'), new OA\Schema(type: 'null')]),
    ],
)]
final class ConversationData extends Resource
{
    public function __construct(
        #[OA\Property(example: 1)] public int $id,
        #[OA\Property(type: 'string', enum: ['edition', 'assignment'])] public ConversationType $type,
        #[OA\Property(type: 'string', enum: ['member', 'giver', 'receiver'])] public string $role,
        public ?ConversationCounterpartData $counterpart,
        #[OA\Property(minimum: 0, example: 2)] public int $unreadCount,
        public ?string $lastMessageAt,
        #[OA\Property(example: true)] public bool $canSend,
    ) {}

    public static function fromConversation(
        Conversation $conversation,
        Edition $edition,
        EditionParticipant $participant,
        int $unreadCount,
        ?string $lastMessageAt,
    ): self {
        if ($conversation->type === ConversationType::Edition) {
            return new self(
                id: $conversation->id,
                type: ConversationType::Edition,
                role: 'member',
                counterpart: null,
                unreadCount: $unreadCount,
                lastMessageAt: $lastMessageAt,
                canSend: $edition->status !== EditionStatus::Archived,
            );
        }

        $assignment = $conversation->getRelation('assignment');

        if (! $assignment instanceof Assignment || $assignment->giver === null || $assignment->receiver === null) {
            throw new \LogicException('An assignment conversation must have both participants loaded.');
        }

        $isGiver = $assignment->giver_edition_participant_id === $participant->id;
        $counterpart = $isGiver ? $assignment->receiver : $assignment->giver;
        $anonymous = ! $isGiver && $edition->status === EditionStatus::Drawn;

        return new self(
            id: $conversation->id,
            type: ConversationType::Assignment,
            role: $isGiver ? 'giver' : 'receiver',
            counterpart: ConversationCounterpartData::fromParticipant($counterpart, $anonymous),
            unreadCount: $unreadCount,
            lastMessageAt: $lastMessageAt,
            canSend: in_array($edition->status, [EditionStatus::Drawn, EditionStatus::Revealed], true),
        );
    }
}

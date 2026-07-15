<?php

namespace App\Data\Api\V1\Conversations;

use App\Models\EditionParticipant;
use App\Models\Message;
use OpenApi\Attributes as OA;
use Spatie\LaravelData\Resource;

#[OA\Schema(schema: 'Message', required: ['id', 'body', 'isMine', 'sentAt'])]
final class MessageData extends Resource
{
    public function __construct(
        #[OA\Property(example: 1)] public int $id,
        #[OA\Property(example: 'Qual tamanho você prefere?', maxLength: 1000)] public string $body,
        #[OA\Property(example: true)] public bool $isMine,
        #[OA\Property(type: 'string', format: 'date-time')] public string $sentAt,
    ) {}

    public static function fromMessage(Message $message, EditionParticipant $participant): self
    {
        if ($message->created_at === null) {
            throw new \LogicException('A persisted message must have a creation timestamp.');
        }

        return new self(
            id: $message->id,
            body: $message->body,
            isMine: $message->sender_edition_participant_id === $participant->id,
            sentAt: $message->created_at->toIso8601String(),
        );
    }
}

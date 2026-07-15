<?php

namespace App\Data\Api\V1\Conversations;

use App\Models\EditionParticipant;
use App\Models\GroupMember;
use App\Models\User;
use OpenApi\Attributes as OA;
use Spatie\LaravelData\Resource;

#[OA\Schema(
    schema: 'ConversationCounterpart',
    required: ['displayName', 'anonymous'],
    properties: [
        new OA\Property(property: 'displayName', oneOf: [new OA\Schema(type: 'string', example: 'João'), new OA\Schema(type: 'null')]),
    ],
)]
final class ConversationCounterpartData extends Resource
{
    public function __construct(
        public ?string $displayName,
        #[OA\Property(example: false)] public bool $anonymous,
    ) {}

    public static function fromParticipant(EditionParticipant $participant, bool $anonymous): self
    {
        if ($anonymous) {
            return new self(null, true);
        }

        $member = $participant->getRelation('groupMember');

        if (! $member instanceof GroupMember) {
            throw new \LogicException('A conversation counterpart must have a group member loaded.');
        }

        $account = $member->getRelation('user');
        $accountName = $account instanceof User ? $account->name : null;

        return new self($member->display_name ?? $accountName ?? __('draw.participant'), false);
    }
}

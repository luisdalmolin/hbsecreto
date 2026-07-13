<?php

namespace App\Data\Api\V1\Draws;

use App\Models\EditionParticipant;
use App\Models\GroupMember;
use OpenApi\Attributes as OA;
use Spatie\LaravelData\Resource;

#[OA\Schema(schema: 'AssignmentParticipant', required: ['participantId', 'groupMemberId', 'displayName'])]
final class AssignmentParticipantData extends Resource
{
    public function __construct(
        #[OA\Property(example: 1)] public int $participantId,
        #[OA\Property(example: 1)] public int $groupMemberId,
        #[OA\Property(example: 'João')] public string $displayName,
    ) {}

    public static function fromParticipant(EditionParticipant $participant): self
    {
        $member = $participant->getRelation('groupMember');

        if (! $member instanceof GroupMember) {
            throw new \LogicException('An assignment participant must have a group member loaded.');
        }

        $accountName = $member->user?->name;

        return new self(
            $participant->id,
            $participant->group_member_id,
            $member->display_name ?? $accountName ?? __('draw.participant'),
        );
    }
}

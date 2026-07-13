<?php

namespace App\Data\Api\V1\Editions;

use App\Data\Api\V1\Groups\GroupMemberData;
use OpenApi\Attributes as OA;
use Spatie\LaravelData\Resource;

#[OA\Schema(schema: 'EditionParticipant', required: ['id', 'editionId', 'groupMember'])]
final class EditionParticipantData extends Resource
{
    public function __construct(
        #[OA\Property(example: 1)] public int $id,
        #[OA\Property(example: 1)] public int $editionId,
        #[OA\Property(ref: '#/components/schemas/GroupMember')] public GroupMemberData $groupMember,
    ) {}
}

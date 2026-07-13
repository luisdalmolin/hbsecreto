<?php

namespace App\Data\Api\V1\Draws;

use OpenApi\Attributes as OA;
use Spatie\LaravelData\Resource;

#[OA\Schema(schema: 'Assignment', required: ['giver', 'receiver'])]
final class AssignmentData extends Resource
{
    public function __construct(
        #[OA\Property(ref: '#/components/schemas/AssignmentParticipant')] public AssignmentParticipantData $giver,
        #[OA\Property(ref: '#/components/schemas/AssignmentParticipant')] public AssignmentParticipantData $receiver,
    ) {}
}

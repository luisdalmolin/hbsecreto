<?php

namespace App\Data\Api\V1\Draws;

use OpenApi\Attributes as OA;
use Spatie\LaravelData\Resource;

#[OA\Schema(schema: 'MyAssignment', required: ['receiver'])]
final class MyAssignmentData extends Resource
{
    public function __construct(
        #[OA\Property(ref: '#/components/schemas/AssignmentParticipant')] public AssignmentParticipantData $receiver,
    ) {}
}

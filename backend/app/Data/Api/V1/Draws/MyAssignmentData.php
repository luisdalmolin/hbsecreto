<?php

namespace App\Data\Api\V1\Draws;

use App\Data\Api\V1\Wishes\WishData;
use OpenApi\Attributes as OA;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Resource;

#[OA\Schema(schema: 'MyAssignment', required: ['receiver', 'wishes'])]
final class MyAssignmentData extends Resource
{
    /** @param DataCollection<int, WishData> $wishes */
    public function __construct(
        #[OA\Property(ref: '#/components/schemas/AssignmentParticipant')] public AssignmentParticipantData $receiver,
        #[OA\Property(type: 'array', items: new OA\Items(ref: '#/components/schemas/Wish'))] public DataCollection $wishes,
    ) {}
}

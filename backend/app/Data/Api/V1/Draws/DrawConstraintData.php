<?php

namespace App\Data\Api\V1\Draws;

use App\Enums\DrawConstraintSource;
use App\Enums\DrawConstraintType;
use App\Models\DrawConstraint;
use OpenApi\Attributes as OA;
use Spatie\LaravelData\Resource;

#[OA\Schema(schema: 'DrawConstraint', required: ['id', 'editionId', 'type', 'giverParticipantId', 'receiverParticipantId', 'source'])]
final class DrawConstraintData extends Resource
{
    public function __construct(
        #[OA\Property(example: 1)] public int $id,
        #[OA\Property(example: 1)] public int $editionId,
        #[OA\Property(type: 'string', enum: ['must_not_pair', 'must_pair'])] public DrawConstraintType $type,
        #[OA\Property(example: 1)] public int $giverParticipantId,
        #[OA\Property(example: 2)] public int $receiverParticipantId,
        #[OA\Property(type: 'string', enum: ['admin'])] public DrawConstraintSource $source,
    ) {}

    public static function fromModel(DrawConstraint $constraint): self
    {
        return new self(
            $constraint->id,
            $constraint->edition_id,
            $constraint->type,
            $constraint->giver_edition_participant_id,
            $constraint->receiver_edition_participant_id,
            $constraint->source,
        );
    }
}

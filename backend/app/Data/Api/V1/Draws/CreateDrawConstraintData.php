<?php

namespace App\Data\Api\V1\Draws;

use App\Enums\DrawConstraintType;
use OpenApi\Attributes as OA;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Data;

#[OA\Schema(schema: 'CreateDrawConstraintRequest', required: ['type', 'giverParticipantId', 'receiverParticipantId'])]
final class CreateDrawConstraintData extends Data
{
    public function __construct(
        #[OA\Property(type: 'string', enum: ['must_not_pair', 'must_pair'])] public DrawConstraintType $type,
        #[IntegerType, Min(1)] #[OA\Property(minimum: 1)] public int $giverParticipantId,
        #[IntegerType, Min(1)] #[OA\Property(minimum: 1)] public int $receiverParticipantId,
    ) {}
}

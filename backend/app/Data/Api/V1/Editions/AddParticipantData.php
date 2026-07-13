<?php

namespace App\Data\Api\V1\Editions;

use OpenApi\Attributes as OA;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Data;

#[OA\Schema(schema: 'AddParticipantRequest', required: ['groupMemberId'])]
final class AddParticipantData extends Data
{
    public function __construct(
        #[IntegerType] #[OA\Property(example: 1)] public int $groupMemberId,
    ) {}
}

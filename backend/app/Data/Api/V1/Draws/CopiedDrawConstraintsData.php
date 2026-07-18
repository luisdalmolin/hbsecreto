<?php

namespace App\Data\Api\V1\Draws;

use App\Actions\DrawConstraints\CopiedDrawConstraints;
use OpenApi\Attributes as OA;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Resource;

#[OA\Schema(
    schema: 'CopiedDrawConstraints',
    required: ['sourceEditionId', 'copiedCount', 'skippedMissingParticipants', 'skippedDuplicates', 'skippedConflicts', 'data'],
    properties: [
        new OA\Property(property: 'sourceEditionId', oneOf: [new OA\Schema(type: 'integer'), new OA\Schema(type: 'null')]),
    ],
)]
final class CopiedDrawConstraintsData extends Resource
{
    /** @param DataCollection<int, DrawConstraintData> $data */
    public function __construct(
        public ?int $sourceEditionId,
        #[OA\Property(minimum: 0)] public int $copiedCount,
        #[OA\Property(minimum: 0)] public int $skippedMissingParticipants,
        #[OA\Property(minimum: 0)] public int $skippedDuplicates,
        #[OA\Property(minimum: 0)] public int $skippedConflicts,
        #[OA\Property(type: 'array', items: new OA\Items(ref: '#/components/schemas/DrawConstraint'))] public DataCollection $data,
    ) {}

    public static function fromResult(CopiedDrawConstraints $result): self
    {
        /** @var DataCollection<int, DrawConstraintData> $data */
        $data = DrawConstraintData::collect($result->constraints, DataCollection::class);

        return new self(
            sourceEditionId: $result->sourceEditionId,
            copiedCount: $result->constraints->count(),
            skippedMissingParticipants: $result->skippedMissingParticipants,
            skippedDuplicates: $result->skippedDuplicates,
            skippedConflicts: $result->skippedConflicts,
            data: $data,
        );
    }
}

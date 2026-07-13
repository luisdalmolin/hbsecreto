<?php

namespace App\Data\Api\V1\Draws;

use OpenApi\Attributes as OA;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Resource;

#[OA\Schema(schema: 'DrawConstraintCollection', required: ['data'])]
final class DrawConstraintCollectionData extends Resource
{
    /** @param DataCollection<int, DrawConstraintData> $data */
    public function __construct(
        #[OA\Property(type: 'array', items: new OA\Items(ref: '#/components/schemas/DrawConstraint'))] public DataCollection $data,
    ) {}
}

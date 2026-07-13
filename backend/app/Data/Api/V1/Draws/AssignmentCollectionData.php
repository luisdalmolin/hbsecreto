<?php

namespace App\Data\Api\V1\Draws;

use OpenApi\Attributes as OA;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Resource;

#[OA\Schema(schema: 'AssignmentCollection', required: ['data'])]
final class AssignmentCollectionData extends Resource
{
    /** @param DataCollection<int, AssignmentData> $data */
    public function __construct(
        #[OA\Property(type: 'array', items: new OA\Items(ref: '#/components/schemas/Assignment'))] public DataCollection $data,
    ) {}
}

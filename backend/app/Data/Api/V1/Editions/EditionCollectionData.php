<?php

namespace App\Data\Api\V1\Editions;

use App\Data\Api\V1\Shared\PaginationMetaData;
use OpenApi\Attributes as OA;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Resource;

#[OA\Schema(schema: 'EditionCollection', required: ['data', 'meta'])]
final class EditionCollectionData extends Resource
{
    /** @param DataCollection<int, EditionData> $data */
    public function __construct(
        #[OA\Property(type: 'array', items: new OA\Items(ref: '#/components/schemas/Edition'))] public DataCollection $data,
        #[OA\Property(ref: '#/components/schemas/PaginationMeta')] public PaginationMetaData $meta,
    ) {}
}

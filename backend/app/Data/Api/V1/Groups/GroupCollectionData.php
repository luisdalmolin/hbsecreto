<?php

namespace App\Data\Api\V1\Groups;

use App\Data\Api\V1\Shared\PaginationMetaData;
use OpenApi\Attributes as OA;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Resource;

#[OA\Schema(schema: 'GroupCollection', required: ['data', 'meta'])]
final class GroupCollectionData extends Resource
{
    /** @param DataCollection<int, GroupData> $data */
    public function __construct(
        #[OA\Property(type: 'array', items: new OA\Items(ref: '#/components/schemas/Group'))]
        public DataCollection $data,
        #[OA\Property(ref: '#/components/schemas/PaginationMeta')]
        public PaginationMetaData $meta,
    ) {}
}

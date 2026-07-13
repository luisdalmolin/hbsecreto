<?php

namespace App\Data\Api\V1\Groups;

use App\Data\Api\V1\Shared\PaginationMetaData;
use OpenApi\Attributes as OA;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Resource;

#[OA\Schema(schema: 'GroupMemberCollection', required: ['data', 'meta'])]
final class GroupMemberCollectionData extends Resource
{
    /** @param DataCollection<int, GroupMemberData> $data */
    public function __construct(
        #[OA\Property(type: 'array', items: new OA\Items(ref: '#/components/schemas/GroupMember'))]
        public DataCollection $data,
        #[OA\Property(ref: '#/components/schemas/PaginationMeta')]
        public PaginationMetaData $meta,
    ) {}
}

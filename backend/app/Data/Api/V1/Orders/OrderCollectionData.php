<?php

namespace App\Data\Api\V1\Orders;

use App\Data\Api\V1\Shared\PaginationMetaData;
use OpenApi\Attributes as OA;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Resource;

#[OA\Schema(
    schema: 'OrderCollection',
    required: ['data', 'meta'],
    properties: [
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Order')),
        new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
    ],
)]
final class OrderCollectionData extends Resource
{
    /** @param DataCollection<int, OrderData> $data */
    public function __construct(
        public DataCollection $data,
        public PaginationMetaData $meta,
    ) {}
}

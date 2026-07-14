<?php

namespace App\Data\Api\V1\Wishes;

use OpenApi\Attributes as OA;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Resource;

#[OA\Schema(schema: 'WishCollection', required: ['data'])]
final class WishCollectionData extends Resource
{
    /** @param DataCollection<int, WishData> $data */
    public function __construct(
        #[OA\Property(type: 'array', items: new OA\Items(ref: '#/components/schemas/Wish'))] public DataCollection $data,
    ) {}
}

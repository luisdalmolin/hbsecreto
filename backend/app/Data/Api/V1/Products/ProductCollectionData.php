<?php

namespace App\Data\Api\V1\Products;

use OpenApi\Attributes as OA;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Resource;

#[OA\Schema(schema: 'ProductCollection', required: ['data'])]
final class ProductCollectionData extends Resource
{
    /** @param DataCollection<int, ProductData> $data */
    public function __construct(
        #[OA\Property(type: 'array', items: new OA\Items(ref: '#/components/schemas/Product'))]
        public DataCollection $data,
    ) {}
}

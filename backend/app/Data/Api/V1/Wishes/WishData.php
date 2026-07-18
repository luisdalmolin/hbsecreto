<?php

namespace App\Data\Api\V1\Wishes;

use App\Data\Api\V1\Products\ProductData;
use App\Models\Wish;
use OpenApi\Attributes as OA;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Resource;

#[OA\Schema(
    schema: 'Wish',
    required: ['id', 'description', 'sortOrder', 'product'],
    properties: [
        new OA\Property(property: 'product', oneOf: [new OA\Schema(ref: '#/components/schemas/Product'), new OA\Schema(type: 'null')]),
    ],
)]
final class WishData extends Resource
{
    public function __construct(
        #[OA\Property(example: 1)] public int $id,
        #[OA\Property(example: 'Um livro de ficção científica', maxLength: 500)] public string $description,
        #[MapInputName('sort_order')] #[OA\Property(minimum: 0, example: 0)] public int $sortOrder,
        public ?ProductData $product,
    ) {}

    public static function fromModel(Wish $wish): self
    {
        $wish->loadMissing('product');

        return new self(
            id: $wish->id,
            description: $wish->description,
            sortOrder: $wish->sort_order,
            product: $wish->product === null ? null : ProductData::fromModel($wish->product),
        );
    }
}

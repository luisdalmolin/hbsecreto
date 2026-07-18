<?php

namespace App\Data\Api\V1\Wishes;

use OpenApi\Attributes as OA;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

#[OA\Schema(schema: 'UpdateWishRequest', required: ['description'])]
final class UpdateWishData extends Data
{
    public function __construct(
        #[Max(500), Regex('/\S/u')] #[OA\Property(example: 'Uma edição especial do livro', minLength: 1, maxLength: 500)] public string $description,
        #[IntegerType, Exists('products', 'id')] #[OA\Property(type: 'integer', nullable: true, example: 1)] public int|Optional|null $productId,
    ) {}
}

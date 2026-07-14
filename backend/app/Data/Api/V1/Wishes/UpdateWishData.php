<?php

namespace App\Data\Api\V1\Wishes;

use OpenApi\Attributes as OA;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Data;

#[OA\Schema(schema: 'UpdateWishRequest', required: ['description'])]
final class UpdateWishData extends Data
{
    public function __construct(
        #[Max(500), Regex('/\S/u')] #[OA\Property(example: 'Uma edição especial do livro', minLength: 1, maxLength: 500)] public string $description,
    ) {}
}

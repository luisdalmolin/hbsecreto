<?php

namespace App\Data\Api\V1\Wishes;

use OpenApi\Attributes as OA;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Data;

#[OA\Schema(schema: 'CreateWishRequest', required: ['description'])]
final class CreateWishData extends Data
{
    public function __construct(
        #[Max(500), Regex('/\S/u')] #[OA\Property(example: 'Um livro de ficção científica', minLength: 1, maxLength: 500)] public string $description,
    ) {}
}

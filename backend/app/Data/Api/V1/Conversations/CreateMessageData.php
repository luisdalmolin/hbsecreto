<?php

namespace App\Data\Api\V1\Conversations;

use OpenApi\Attributes as OA;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Data;

#[OA\Schema(schema: 'CreateMessageRequest', required: ['body'])]
final class CreateMessageData extends Data
{
    public function __construct(
        #[Max(1000), Regex('/\S/u')] #[OA\Property(example: 'Qual tamanho você prefere?', minLength: 1, maxLength: 1000)] public string $body,
    ) {}
}

<?php

namespace App\Data\Api\V1\Shared;

use OpenApi\Attributes as OA;
use Spatie\LaravelData\Resource;

#[OA\Schema(schema: 'Error', required: ['message'])]
final class ErrorData extends Resource
{
    /**
     * @param  array<string, array<string>>  $errors
     */
    public function __construct(
        #[OA\Property(example: 'Não autenticado.')]
        public string $message,
        #[OA\Property(type: 'object')]
        public array $errors = [],
    ) {}
}

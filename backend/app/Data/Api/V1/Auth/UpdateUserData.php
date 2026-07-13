<?php

namespace App\Data\Api\V1\Auth;

use OpenApi\Attributes as OA;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

#[OA\Schema(schema: 'UpdateUserRequest')]
final class UpdateUserData extends Data
{
    public function __construct(
        #[Max(255)] #[OA\Property(type: 'string', maxLength: 255)] public string|Optional $name,
        #[In('pt-BR')] #[OA\Property(type: 'string', enum: ['pt-BR'])] public string|Optional $locale,
    ) {}
}

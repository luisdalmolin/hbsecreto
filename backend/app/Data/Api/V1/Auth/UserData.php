<?php

namespace App\Data\Api\V1\Auth;

use OpenApi\Attributes as OA;
use Spatie\LaravelData\Resource;

#[OA\Schema(schema: 'User')]
final class UserData extends Resource
{
    public function __construct(
        #[OA\Property(example: 1)]
        public int $id,
        #[OA\Property(example: 'Ana Silva')]
        public string $name,
        #[OA\Property(format: 'email', example: 'ana@example.com')]
        public string $email,
    ) {}
}

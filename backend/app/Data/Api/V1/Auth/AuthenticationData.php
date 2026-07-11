<?php

namespace App\Data\Api\V1\Auth;

use OpenApi\Attributes as OA;
use Spatie\LaravelData\Resource;

#[OA\Schema(schema: 'Authentication')]
final class AuthenticationData extends Resource
{
    public function __construct(
        #[OA\Property(example: '1|secret-token')]
        public string $accessToken,
        #[OA\Property(example: 'Bearer')]
        public string $tokenType,
        #[OA\Property(ref: '#/components/schemas/User')]
        public UserData $user,
    ) {}
}

<?php

namespace App\Data\Api\V1\Auth;

use OpenApi\Attributes as OA;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Data;

#[OA\Schema(
    schema: 'LoginRequest',
    required: ['email', 'password', 'deviceName'],
)]
final class LoginData extends Data
{
    public function __construct(
        #[Email]
        #[OA\Property(format: 'email', example: 'ana@example.com')]
        public string $email,
        #[OA\Property(format: 'password', example: 'password')]
        public string $password,
        #[Max(255)]
        #[OA\Property(maxLength: 255, example: 'Ana’s iPhone')]
        public string $deviceName,
    ) {}
}

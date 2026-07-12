<?php

namespace App\Data\Api\V1\Auth;

use OpenApi\Attributes as OA;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Password;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Data;

#[OA\Schema(
    schema: 'RegisterRequest',
    required: ['name', 'email', 'password', 'deviceName'],
)]
final class RegisterData extends Data
{
    public function __construct(
        #[Max(255)]
        #[OA\Property(maxLength: 255, example: 'Ana Silva')]
        public string $name,
        #[Email]
        #[Unique('users', 'email')]
        #[OA\Property(format: 'email', example: 'ana@example.com')]
        public string $email,
        #[Password(default: true)]
        #[OA\Property(format: 'password', minLength: 8, example: 'password')]
        public string $password,
        #[Max(255)]
        #[OA\Property(maxLength: 255, example: 'Ana’s iPhone')]
        public string $deviceName,
    ) {}
}

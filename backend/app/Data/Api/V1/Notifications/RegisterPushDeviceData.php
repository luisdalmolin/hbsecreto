<?php

namespace App\Data\Api\V1\Notifications;

use App\Enums\PushPlatform;
use OpenApi\Attributes as OA;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Data;

#[OA\Schema(schema: 'RegisterPushDeviceRequest', required: ['expoPushToken', 'platform'])]
final class RegisterPushDeviceData extends Data
{
    public function __construct(
        #[Max(255), Regex('/^(ExponentPushToken|ExpoPushToken)\[[A-Za-z0-9_-]+\]$/')]
        #[OA\Property(example: 'ExponentPushToken[xxxxxxxxxxxxxxxxxxxxxx]', maxLength: 255)]
        public string $expoPushToken,
        #[OA\Property(type: 'string', enum: ['ios', 'android'])]
        public PushPlatform $platform,
        #[Max(255)]
        #[OA\Property(nullable: true, example: 'iPhone de Maria', maxLength: 255)]
        public ?string $deviceName = null,
    ) {}
}

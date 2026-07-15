<?php

namespace App\Data\Api\V1\Notifications;

use App\Enums\PushPlatform;
use App\Models\PushDevice;
use OpenApi\Attributes as OA;
use Spatie\LaravelData\Resource;

#[OA\Schema(schema: 'PushDevice', required: ['id', 'platform', 'registeredAt'])]
final class PushDeviceData extends Resource
{
    public function __construct(
        #[OA\Property(example: 1)] public int $id,
        #[OA\Property(type: 'string', enum: ['ios', 'android'])] public PushPlatform $platform,
        #[OA\Property(nullable: true, example: 'iPhone de Maria')] public ?string $deviceName,
        #[OA\Property(type: 'string', format: 'date-time')] public string $registeredAt,
    ) {}

    public static function fromPushDevice(PushDevice $device): self
    {
        return new self(
            id: $device->id,
            platform: $device->platform,
            deviceName: $device->device_name,
            registeredAt: $device->last_registered_at->toIso8601String(),
        );
    }
}

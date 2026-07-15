<?php

namespace App\Actions\Notifications;

use App\Enums\PushPlatform;
use App\Models\PushDevice;
use App\Models\User;

final class RegisterPushDevice
{
    public function handle(User $user, string $expoPushToken, PushPlatform $platform, ?string $deviceName): PushDevice
    {
        $accessToken = $user->currentAccessToken();

        PushDevice::query()->upsert([
            [
                'user_id' => $user->id,
                'personal_access_token_id' => $accessToken->id,
                'expo_push_token' => $expoPushToken,
                'platform' => $platform->value,
                'device_name' => $deviceName,
                'last_registered_at' => now(),
                'disabled_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ], ['expo_push_token'], [
            'user_id',
            'personal_access_token_id',
            'platform',
            'device_name',
            'last_registered_at',
            'disabled_at',
            'updated_at',
        ]);

        return PushDevice::query()->where('expo_push_token', $expoPushToken)->firstOrFail();
    }
}

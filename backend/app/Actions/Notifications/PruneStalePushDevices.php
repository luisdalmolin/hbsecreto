<?php

namespace App\Actions\Notifications;

use App\Models\PushDevice;

final class PruneStalePushDevices
{
    public function handle(int $staleAfterDays): int
    {
        $devices = PushDevice::query()
            ->where('last_registered_at', '<', now()->subDays($staleAfterDays))
            ->get();
        $deleted = 0;

        foreach ($devices as $device) {
            if ($device->delete()) {
                $deleted++;
            }
        }

        return $deleted;
    }
}

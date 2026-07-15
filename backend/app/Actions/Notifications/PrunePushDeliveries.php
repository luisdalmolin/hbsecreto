<?php

namespace App\Actions\Notifications;

use App\Enums\PushDeliveryStatus;
use App\Models\PushDelivery;

final class PrunePushDeliveries
{
    public function handle(int $retentionDays): int
    {
        $deliveries = PushDelivery::query()
            ->whereIn('status', [
                PushDeliveryStatus::Delivered,
                PushDeliveryStatus::Failed,
                PushDeliveryStatus::Expired,
            ])
            ->where('completed_at', '<', now()->subDays($retentionDays))
            ->get();
        $deleted = 0;

        foreach ($deliveries as $delivery) {
            if ($delivery->delete()) {
                $deleted++;
            }
        }

        return $deleted;
    }
}

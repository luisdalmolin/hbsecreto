<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Models\Edition;

final class ReconcileExpiredPendingOrders
{
    public function handle(Edition $edition): int
    {
        return $edition->orders()
            ->where('status', OrderStatus::Pending)
            ->whereNotNull('checkout_expires_at')
            ->where('checkout_expires_at', '<=', now())
            ->update(['status' => OrderStatus::Failed]);
    }
}

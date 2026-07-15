<?php

namespace App\Actions\Notifications;

final readonly class ReconcilePushReceiptsResult
{
    public function __construct(
        public int $checked,
        public int $delivered,
        public int $failed,
        public int $missing,
        public int $expired,
    ) {}
}

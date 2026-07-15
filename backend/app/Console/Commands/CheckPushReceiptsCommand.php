<?php

namespace App\Console\Commands;

use App\Actions\Notifications\ReconcilePushReceipts;
use Illuminate\Console\Command;

final class CheckPushReceiptsCommand extends Command
{
    protected $signature = 'notifications:check-push-receipts';

    protected $description = 'Fetch Expo push receipts and update delivery records';

    public function handle(ReconcilePushReceipts $reconcile): int
    {
        $result = $reconcile->handle();

        $this->table(['Checked', 'Delivered', 'Failed', 'Missing', 'Expired'], [[
            $result->checked,
            $result->delivered,
            $result->failed,
            $result->missing,
            $result->expired,
        ]]);

        return self::SUCCESS;
    }
}

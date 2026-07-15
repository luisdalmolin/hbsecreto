<?php

namespace App\Console\Commands;

use App\Actions\Notifications\PrunePushDeliveries;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

final class PrunePushDeliveriesCommand extends Command
{
    protected $signature = 'notifications:prune-push-deliveries {--days= : Override the configured audit retention}';

    protected $description = 'Delete completed push delivery audit records outside the retention window';

    public function handle(PrunePushDeliveries $prune): int
    {
        $days = $this->option('days');
        $retentionDays = is_numeric($days) ? (int) $days : Config::integer('services.expo_push.delivery_retention_days');

        if ($retentionDays < 1) {
            $this->error('The audit retention must be at least one day.');

            return self::INVALID;
        }

        $deleted = $prune->handle($retentionDays);
        $this->info("Deleted {$deleted} completed push delivery record(s).");

        return self::SUCCESS;
    }
}

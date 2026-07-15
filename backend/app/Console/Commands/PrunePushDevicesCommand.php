<?php

namespace App\Console\Commands;

use App\Actions\Notifications\PruneStalePushDevices;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

final class PrunePushDevicesCommand extends Command
{
    protected $signature = 'notifications:prune-push-devices {--days= : Override the configured stale-device age}';

    protected $description = 'Delete stale Expo push device registrations';

    public function handle(PruneStalePushDevices $prune): int
    {
        $days = $this->option('days');
        $staleAfterDays = is_numeric($days) ? (int) $days : Config::integer('services.expo_push.stale_device_days');

        if ($staleAfterDays < 1) {
            $this->error('The stale-device age must be at least one day.');

            return self::INVALID;
        }

        $deleted = $prune->handle($staleAfterDays);
        $this->info("Deleted {$deleted} stale push device registration(s).");

        return self::SUCCESS;
    }
}

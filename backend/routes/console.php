<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('notifications:check-push-receipts')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('notifications:prune-push-devices')->daily()->withoutOverlapping();
Schedule::command('notifications:prune-push-deliveries')->daily()->withoutOverlapping();

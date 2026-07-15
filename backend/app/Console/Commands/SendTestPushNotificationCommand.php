<?php

namespace App\Console\Commands;

use App\Models\PushDelivery;
use App\Models\User;
use App\Notifications\Channels\ExpoPushChannel;
use App\Notifications\PushDiagnosticNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

final class SendTestPushNotificationCommand extends Command
{
    protected $signature = 'notifications:test-push {user : User ID or email address}';

    protected $description = 'Send a diagnostic Expo push notification to a user';

    public function handle(): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('Diagnostic push notifications are only available in local and testing environments.');

            return self::FAILURE;
        }

        $identifier = (string) $this->argument('user');
        $user = User::query()
            ->where(fn ($query) => $query
                ->when(is_numeric($identifier), fn ($users) => $users->whereKey((int) $identifier))
                ->when(! is_numeric($identifier), fn ($users) => $users->where('email', $identifier)))
            ->first();

        if ($user === null) {
            $this->error('User not found.');

            return self::FAILURE;
        }

        if (! $user->pushDevices()->active()->exists()) {
            $this->error('The user has no active push devices.');

            return self::FAILURE;
        }

        $notification = new PushDiagnosticNotification;
        $notification->id = Str::uuid()->toString();
        Notification::sendNow($user, $notification, [ExpoPushChannel::class]);
        $deliveries = PushDelivery::query()
            ->where('notification_id', $notification->id)
            ->orderBy('id')
            ->get();

        $this->table(
            ['Device', 'Status', 'Expo ticket', 'Error'],
            $deliveries->map(fn (PushDelivery $delivery): array => [
                $delivery->push_device_id ?? 'deleted',
                $delivery->status->value,
                $delivery->expo_ticket_id ?? '—',
                $delivery->error_code ?? '—',
            ])->all(),
        );
        $this->info('Run notifications:check-push-receipts after 15 minutes to inspect delivery receipts.');

        return self::SUCCESS;
    }
}

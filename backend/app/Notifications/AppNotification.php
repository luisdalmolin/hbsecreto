<?php

namespace App\Notifications;

use App\Enums\AppNotificationType;
use App\Models\User;
use App\Notifications\Channels\ExpoPushChannel;
use App\Notifications\ExpoPush\ExpoPushContent;
use App\Notifications\ExpoPush\SendsExpoPush;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Config;
use LogicException;

abstract class AppNotification extends Notification implements SendsExpoPush, ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [1, 5, 15];

    public function __construct()
    {
        $this->afterCommit();
    }

    /** @return array<int, string> */
    final public function via(object $notifiable): array
    {
        return ['database', ExpoPushChannel::class];
    }

    /** @return array<string, string> */
    final public function viaConnections(): array
    {
        return [
            'database' => 'sync',
            ExpoPushChannel::class => Config::string('queue.default'),
        ];
    }

    /** @return array<string, string> */
    final public function viaQueues(): array
    {
        return [ExpoPushChannel::class => 'notifications'];
    }

    /** @return array{title: string, body: string, url: string} */
    final public function toDatabase(object $notifiable): array
    {
        return $this->payload($this->user($notifiable))->toDatabase();
    }

    final public function toExpoPush(object $notifiable): ExpoPushContent
    {
        return $this->payload($this->user($notifiable))->toExpoPush($this->id);
    }

    final public function databaseType(object $notifiable): string
    {
        return $this->type()->value;
    }

    abstract public function type(): AppNotificationType;

    abstract protected function payload(User $user): AppNotificationPayload;

    private function user(object $notifiable): User
    {
        if (! $notifiable instanceof User) {
            throw new LogicException('Application notifications may only be sent to users.');
        }

        return $notifiable;
    }
}

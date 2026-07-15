<?php

namespace App\Notifications\Channels;

use App\Models\PushDevice;
use App\Models\User;
use App\Notifications\ExpoPush\ExpoPushMessage;
use App\Notifications\ExpoPush\ExpoPushResult;
use App\Notifications\ExpoPush\ExpoPushTransport;
use App\Notifications\ExpoPush\SendsExpoPush;
use Illuminate\Notifications\Notification;
use LogicException;

final readonly class ExpoPushChannel
{
    public function __construct(private ExpoPushTransport $transport) {}

    public function send(object $notifiable, Notification $notification): ?ExpoPushResult
    {
        if (! $notifiable instanceof User || ! $notification instanceof SendsExpoPush) {
            throw new LogicException('Expo push notifications require a User and the SendsExpoPush contract.');
        }

        $content = $notification->toExpoPush($notifiable);
        $devices = $notifiable->pushDevices()->active()->get();

        if ($devices->isEmpty()) {
            return null;
        }

        $messages = $devices->map(
            fn (PushDevice $device): ExpoPushMessage => new ExpoPushMessage($device->expo_push_token, $content),
        );
        $result = $this->transport->send(...$messages->all());

        if ($result->invalidTokens !== []) {
            PushDevice::query()
                ->whereIn('expo_push_token', $result->invalidTokens)
                ->update(['disabled_at' => now()]);
        }

        return $result;
    }
}

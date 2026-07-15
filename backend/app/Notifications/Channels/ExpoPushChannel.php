<?php

namespace App\Notifications\Channels;

use App\Enums\PushDeliveryStatus;
use App\Models\PushDelivery;
use App\Models\PushDevice;
use App\Models\User;
use App\Notifications\ExpoPush\ExpoPushMessage;
use App\Notifications\ExpoPush\ExpoPushResult;
use App\Notifications\ExpoPush\ExpoPushTransport;
use App\Notifications\ExpoPush\SendsExpoPush;
use Illuminate\Notifications\Notification;
use LogicException;
use Throwable;

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
        $deliveries = $devices->mapWithKeys(function (PushDevice $device) use ($notification): array {
            $delivery = PushDelivery::query()->updateOrCreate(
                [
                    'notification_id' => $notification->id,
                    'push_device_id' => $device->id,
                ],
                [
                    'notification_type' => $notification->pushNotificationType(),
                    'expo_push_token_hash' => hash('sha256', $device->expo_push_token),
                    'expo_ticket_id' => null,
                    'status' => PushDeliveryStatus::Pending,
                    'error_code' => null,
                    'error_message' => null,
                    'attempted_at' => now(),
                    'receipt_checked_at' => null,
                    'completed_at' => null,
                ],
            );

            return [$device->expo_push_token => $delivery];
        });

        try {
            $result = $this->transport->send(...$messages->all());
        } catch (Throwable $exception) {
            PushDelivery::query()
                ->whereKey($deliveries->pluck('id'))
                ->update([
                    'status' => PushDeliveryStatus::Failed,
                    'error_code' => 'ExpoTransportError',
                    'error_message' => mb_substr($exception->getMessage(), 0, 1000),
                    'completed_at' => now(),
                ]);

            throw $exception;
        }

        foreach ($result->tickets as $ticket) {
            /** @var PushDelivery|null $delivery */
            $delivery = $deliveries->get($ticket->token);
            if ($delivery === null) {
                continue;
            }

            $delivery->update([
                'expo_ticket_id' => $ticket->ticketId,
                'status' => $ticket->accepted ? PushDeliveryStatus::Accepted : PushDeliveryStatus::Failed,
                'error_code' => $ticket->errorCode,
                'error_message' => $ticket->errorMessage,
                'completed_at' => $ticket->accepted ? null : now(),
            ]);
        }

        if ($result->invalidTokens() !== []) {
            PushDevice::query()
                ->whereIn('expo_push_token', $result->invalidTokens())
                ->update(['disabled_at' => now()]);
        }

        return $result;
    }
}

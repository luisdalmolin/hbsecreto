<?php

namespace App\Actions\Notifications;

use App\Enums\PushDeliveryStatus;
use App\Models\PushDelivery;
use App\Models\PushDevice;
use App\Notifications\ExpoPush\ExpoPushTransport;
use Illuminate\Support\Facades\Config;

final readonly class ReconcilePushReceipts
{
    public function __construct(private ExpoPushTransport $transport) {}

    public function handle(): ReconcilePushReceiptsResult
    {
        $now = now();
        $expired = PushDelivery::query()
            ->where('status', PushDeliveryStatus::Accepted)
            ->where('attempted_at', '<=', $now->copy()->subHours(Config::integer('services.expo_push.receipt_expiry_hours')))
            ->update([
                'status' => PushDeliveryStatus::Expired,
                'error_code' => 'ExpoReceiptExpired',
                'error_message' => 'Expo did not return a push receipt before its retention window expired.',
                'completed_at' => $now,
            ]);
        $checked = 0;
        $delivered = 0;
        $failed = 0;
        $missing = 0;

        do {
            $deliveries = PushDelivery::query()
                ->where('status', PushDeliveryStatus::Accepted)
                ->whereNotNull('expo_ticket_id')
                ->where('attempted_at', '<=', $now->copy()->subMinutes(Config::integer('services.expo_push.receipt_delay_minutes')))
                ->where(function ($query) use ($now): void {
                    $query->whereNull('receipt_checked_at')
                        ->orWhere('receipt_checked_at', '<=', $now->copy()->subMinutes(Config::integer('services.expo_push.receipt_retry_minutes')));
                })
                ->orderBy('id')
                ->limit(1000)
                ->get();

            if ($deliveries->isEmpty()) {
                break;
            }

            $ticketIds = $deliveries->map(function (PushDelivery $delivery): string {
                if ($delivery->expo_ticket_id === null) {
                    throw new \LogicException('A delivery awaiting a receipt must have an Expo ticket ID.');
                }

                return $delivery->expo_ticket_id;
            });
            $receipts = $this->transport->receipts(...$ticketIds->all());

            foreach ($deliveries as $delivery) {
                $checked++;
                $receipt = $delivery->expo_ticket_id === null ? null : ($receipts[$delivery->expo_ticket_id] ?? null);

                if ($receipt === null) {
                    $missing++;
                    $delivery->update(['receipt_checked_at' => $now]);

                    continue;
                }

                if ($receipt->delivered) {
                    $delivered++;
                    $delivery->update([
                        'status' => PushDeliveryStatus::Delivered,
                        'receipt_checked_at' => $now,
                        'completed_at' => $now,
                    ]);
                } else {
                    $failed++;
                    $delivery->update([
                        'status' => PushDeliveryStatus::Failed,
                        'error_code' => $receipt->errorCode,
                        'error_message' => $receipt->errorMessage,
                        'receipt_checked_at' => $now,
                        'completed_at' => $now,
                    ]);

                    if ($receipt->errorCode === 'DeviceNotRegistered' && $delivery->push_device_id !== null) {
                        PushDevice::query()->whereKey($delivery->push_device_id)->update(['disabled_at' => $now]);
                    }
                }
            }
        } while ($deliveries->count() === 1000);

        return new ReconcilePushReceiptsResult($checked, $delivered, $failed, $missing, $expired);
    }
}

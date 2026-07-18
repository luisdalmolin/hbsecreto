<?php

namespace App\Actions\Orders;

use App\Models\PaymentWebhookEvent;
use App\Services\Payments\PaymentGateway;
use Illuminate\Support\Facades\DB;

final readonly class ProcessPaymentWebhook
{
    public function __construct(
        private PaymentGateway $gateway,
        private ApplyPaymentRecord $applyPayment,
    ) {}

    /** @param array<string, mixed> $payload */
    public function handle(
        string $signature,
        ?string $requestId,
        string $providerEventId,
        string $resourceId,
        array $payload,
    ): void {
        $this->gateway->verifyWebhook($signature, $requestId, $resourceId);
        $event = PaymentWebhookEvent::query()->firstOrCreate(
            [
                'payment_provider' => $this->gateway->provider(),
                'provider_event_id' => $providerEventId,
            ],
            [
                'resource_id' => $resourceId,
                'payload' => $payload,
            ],
        );

        if ($event->processed_at !== null) {
            return;
        }

        $payment = $this->gateway->findPayment($resourceId);
        $this->applyPayment->handle($payment, $this->gateway->provider());

        DB::transaction(function () use ($event): void {
            $lockedEvent = PaymentWebhookEvent::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();

            if ($lockedEvent->processed_at === null) {
                $lockedEvent->update(['processed_at' => now()]);
            }
        }, attempts: 3);
    }
}

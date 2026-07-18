<?php

namespace App\Services\Payments;

use App\Models\Order;

interface PaymentGateway
{
    public function provider(): string;

    public function createCheckout(Order $order, string $buyerEmail): PaymentCheckoutData;

    public function verifyWebhook(string $signature, ?string $requestId, string $resourceId): void;

    public function findPayment(string $providerReference): PaymentRecordData;

    public function refund(string $providerReference, string $idempotencyKey): PaymentRecordData;
}

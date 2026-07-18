<?php

namespace App\Services\Payments;

use App\Exceptions\InvalidPaymentWebhook;
use App\Models\Order;
use Carbon\CarbonImmutable;

final class FakePaymentGateway implements PaymentGateway
{
    /** @var array<string, PaymentRecordData> */
    private array $payments = [];

    /** @var list<int> */
    private array $checkoutOrderIds = [];

    /** @var list<string> */
    private array $checkoutIdempotencyKeys = [];

    /** @var list<string> */
    private array $refundIdempotencyKeys = [];

    private int $refundFailuresRemaining = 0;

    public function provider(): string
    {
        return 'mercadopago';
    }

    public function createCheckout(Order $order, string $buyerEmail): PaymentCheckoutData
    {
        $this->checkoutOrderIds[] = $order->id;
        $this->checkoutIdempotencyKeys[] = $order->checkout_idempotency_key ?? throw new \LogicException('A checkout idempotency key is required.');
        $reference = 'preference-'.$order->id.'-'.count($this->checkoutOrderIds);

        return new PaymentCheckoutData(
            providerReference: $reference,
            checkoutUrl: 'https://sandbox.mercadopago.test/checkout/'.$reference,
            providerPayload: ['id' => $reference, 'payer' => ['email' => $buyerEmail]],
        );
    }

    public function verifyWebhook(string $signature, ?string $requestId, string $resourceId): void
    {
        if ($signature !== 'valid-signature') {
            throw new InvalidPaymentWebhook('The payment webhook signature is invalid.');
        }
    }

    public function findPayment(string $providerReference): PaymentRecordData
    {
        return $this->payments[$providerReference] ?? throw new \UnexpectedValueException('The fake payment does not exist.');
    }

    public function refund(string $providerReference, string $idempotencyKey): PaymentRecordData
    {
        $this->refundIdempotencyKeys[] = $idempotencyKey;

        if ($this->refundFailuresRemaining > 0) {
            $this->refundFailuresRemaining--;

            throw new \UnexpectedValueException('The fake refund failed.');
        }

        $payment = $this->findPayment($providerReference);
        $refunded = new PaymentRecordData(
            providerReference: $payment->providerReference,
            externalReference: $payment->externalReference,
            status: 'refunded',
            statusDetail: 'refunded',
            amountCents: $payment->amountCents,
            currency: $payment->currency,
            paidAt: $payment->paidAt,
            providerPayload: [...$payment->providerPayload, 'status' => 'refunded'],
        );
        $this->payments[$providerReference] = $refunded;

        return $refunded;
    }

    public function addPayment(
        string $providerReference,
        int|string $orderId,
        string $status,
        int $amountCents = 499,
        string $currency = 'BRL',
        ?CarbonImmutable $paidAt = null,
    ): void {
        $externalReference = is_int($orderId) || ctype_digit($orderId)
            ? CheckoutAttemptReference::forOrder(Order::query()->findOrFail((int) $orderId))->toString()
            : $orderId;
        $this->payments[$providerReference] = new PaymentRecordData(
            providerReference: $providerReference,
            externalReference: $externalReference,
            status: $status,
            statusDetail: $status,
            amountCents: $amountCents,
            currency: $currency,
            paidAt: $paidAt,
            providerPayload: ['id' => $providerReference, 'status' => $status],
        );
    }

    /** @return list<int> */
    public function checkoutOrderIds(): array
    {
        return $this->checkoutOrderIds;
    }

    /** @return list<string> */
    public function checkoutIdempotencyKeys(): array
    {
        return $this->checkoutIdempotencyKeys;
    }

    /** @return list<string> */
    public function refundIdempotencyKeys(): array
    {
        return $this->refundIdempotencyKeys;
    }

    public function failNextRefunds(int $count = 1): void
    {
        $this->refundFailuresRemaining = $count;
    }
}

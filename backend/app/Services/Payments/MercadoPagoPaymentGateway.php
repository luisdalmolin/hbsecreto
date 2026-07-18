<?php

namespace App\Services\Payments;

use App\Exceptions\InvalidPaymentWebhook;
use App\Models\Order;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Throwable;
use UnexpectedValueException;

final readonly class MercadoPagoPaymentGateway implements PaymentGateway
{
    public function __construct(
        private Factory $http,
        private string $baseUrl,
        private string $accessToken,
        private string $webhookSecret,
        private string $webhookUrl,
        private string $returnUrl,
        private int $checkoutExpiryMinutes,
        private int $webhookToleranceSeconds,
        private int $timeout,
        private int $connectTimeout,
    ) {}

    public function provider(): string
    {
        return 'mercadopago';
    }

    public function createCheckout(Order $order, string $buyerEmail): PaymentCheckoutData
    {
        $attemptReference = CheckoutAttemptReference::forOrder($order);

        $response = $this->request()
            ->withHeader('X-Idempotency-Key', $attemptReference->attemptId)
            ->post('/checkout/preferences', [
                'items' => [[
                    'id' => 'pick-purchase-'.$order->id,
                    'title' => __('orders.pick_purchase_title'),
                    'description' => __('orders.pick_purchase_description'),
                    'quantity' => 1,
                    'currency_id' => $order->currency,
                    'unit_price' => $order->amount_cents / 100,
                ]],
                'payer' => ['email' => $buyerEmail],
                'external_reference' => $attemptReference->toString(),
                'notification_url' => $this->webhookUrl,
                'back_urls' => [
                    'success' => $this->returnUrl.'/success?orderId='.$order->id,
                    'pending' => $this->returnUrl.'/pending?orderId='.$order->id,
                    'failure' => $this->returnUrl.'/failure?orderId='.$order->id,
                ],
                'auto_return' => 'approved',
                'binary_mode' => true,
                'expires' => true,
                'expiration_date_to' => now()->addMinutes($this->checkoutExpiryMinutes)->toIso8601String(),
            ])->throw();
        $payload = $response->json();

        if (! is_array($payload)) {
            throw new UnexpectedValueException('Mercado Pago returned an invalid checkout response.');
        }

        $providerPayload = $this->associativePayload($payload);
        $reference = Arr::get($providerPayload, 'id');
        $checkoutUrl = Arr::get($providerPayload, 'init_point');

        if (! is_string($reference) || ! is_string($checkoutUrl) || ! $this->isTrustedCheckoutUrl($checkoutUrl)) {
            throw new UnexpectedValueException('Mercado Pago returned a checkout without an ID or URL.');
        }

        return new PaymentCheckoutData($reference, $checkoutUrl, $providerPayload);
    }

    private function isTrustedCheckoutUrl(string $checkoutUrl): bool
    {
        if (filter_var($checkoutUrl, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $parts = parse_url($checkoutUrl);

        return is_array($parts)
            && ($parts['scheme'] ?? null) === 'https'
            && is_string($parts['host'] ?? null)
            && $parts['host'] !== ''
            && ! isset($parts['user'])
            && ! isset($parts['pass']);
    }

    public function verifyWebhook(string $signature, ?string $requestId, string $resourceId): void
    {
        $parts = [];

        foreach (explode(',', $signature) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, null);

            if (is_string($key) && is_string($value)) {
                $parts[$key] = $value;
            }
        }

        $timestamp = $parts['ts'] ?? null;
        $expectedSignature = $parts['v1'] ?? null;

        if (! is_string($timestamp) || ! ctype_digit($timestamp) || ! is_string($expectedSignature)) {
            throw new InvalidPaymentWebhook('The Mercado Pago signature header is malformed.');
        }

        $normalizedResourceId = Str::lower($resourceId);
        $manifest = 'id:'.$normalizedResourceId.';';

        if ($requestId !== null && $requestId !== '') {
            $manifest .= 'request-id:'.$requestId.';';
        }

        $manifest .= 'ts:'.$timestamp.';';
        $calculatedSignature = hash_hmac('sha256', $manifest, $this->webhookSecret);

        if (! hash_equals($calculatedSignature, $expectedSignature)) {
            throw new InvalidPaymentWebhook('The Mercado Pago signature does not match.');
        }

        $timestampSeconds = (int) $timestamp;

        if ($timestampSeconds > 100_000_000_000) {
            $timestampSeconds = intdiv($timestampSeconds, 1000);
        }

        if ($this->webhookToleranceSeconds > 0 && abs(now()->getTimestamp() - $timestampSeconds) > $this->webhookToleranceSeconds) {
            throw new InvalidPaymentWebhook('The Mercado Pago signature has expired.');
        }
    }

    public function findPayment(string $providerReference): PaymentRecordData
    {
        $response = $this->request()->get('/v1/payments/'.$providerReference)->throw();
        $payload = $response->json();

        if (! is_array($payload)) {
            throw new UnexpectedValueException('Mercado Pago returned an invalid payment response.');
        }

        return $this->paymentData($this->associativePayload($payload));
    }

    public function refund(string $providerReference, string $idempotencyKey): PaymentRecordData
    {
        $this->request()
            ->withHeader('X-Idempotency-Key', $idempotencyKey)
            ->post('/v1/payments/'.$providerReference.'/refunds')
            ->throw();

        return $this->findPayment($providerReference);
    }

    private function request(): PendingRequest
    {
        if ($this->accessToken === '') {
            throw new \LogicException('The Mercado Pago access token is not configured.');
        }

        return $this->http
            ->baseUrl($this->baseUrl)
            ->acceptJson()
            ->asJson()
            ->withToken($this->accessToken)
            ->connectTimeout($this->connectTimeout)
            ->timeout($this->timeout)
            ->retry(
                [100, 500, 1000],
                when: fn (Throwable $exception): bool => $exception instanceof ConnectionException
                    || ($exception instanceof RequestException && ($exception->response->serverError() || $exception->response->status() === 429)),
            );
    }

    /** @param array<string, mixed> $payload */
    private function paymentData(array $payload): PaymentRecordData
    {
        $reference = Arr::get($payload, 'id');
        $externalReference = Arr::get($payload, 'external_reference');
        $status = Arr::get($payload, 'status');
        $statusDetail = Arr::get($payload, 'status_detail');
        $currency = Arr::get($payload, 'currency_id');
        $amount = Arr::get($payload, 'transaction_amount');
        $approvedAt = Arr::get($payload, 'date_approved');

        if ((! is_int($reference) && ! is_string($reference))
            || ! is_string($externalReference)
            || ! is_string($status)
            || ! is_string($currency)
            || (! is_int($amount) && ! is_float($amount) && ! is_string($amount))) {
            throw new UnexpectedValueException('Mercado Pago returned an incomplete payment response.');
        }

        if ($statusDetail !== null && ! is_string($statusDetail)) {
            throw new UnexpectedValueException('Mercado Pago returned an invalid payment status detail.');
        }

        if ($approvedAt !== null && ! is_string($approvedAt)) {
            throw new UnexpectedValueException('Mercado Pago returned an invalid approval timestamp.');
        }

        return new PaymentRecordData(
            providerReference: (string) $reference,
            externalReference: $externalReference,
            status: $status,
            statusDetail: $statusDetail,
            amountCents: (int) round(((float) $amount) * 100),
            currency: $currency,
            paidAt: $approvedAt === null ? null : CarbonImmutable::parse($approvedAt),
            providerPayload: $payload,
        );
    }

    /**
     * @param  array<mixed, mixed>  $payload
     * @return array<string, mixed>
     */
    private function associativePayload(array $payload): array
    {
        $result = [];

        foreach ($payload as $key => $value) {
            if (! is_string($key)) {
                throw new UnexpectedValueException('Mercado Pago returned a payload with an invalid key.');
            }

            $result[$key] = $value;
        }

        return $result;
    }
}

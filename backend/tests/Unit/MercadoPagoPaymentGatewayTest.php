<?php

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Exceptions\InvalidPaymentWebhook;
use App\Models\Order;
use App\Services\Payments\MercadoPagoPaymentGateway;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

function mercadoPagoGateway(): MercadoPagoPaymentGateway
{
    return new MercadoPagoPaymentGateway(
        http: app(Factory::class),
        baseUrl: 'https://api.mercadopago.com',
        accessToken: 'access-token',
        webhookSecret: 'webhook-secret',
        webhookUrl: 'https://example.test/api/v1/payments/mercadopago/webhook',
        returnUrl: 'cpxsecreto://payments',
        checkoutExpiryMinutes: 30,
        webhookToleranceSeconds: 300,
        timeout: 10,
        connectTimeout: 3,
    );
}

function mercadoPagoOrder(): Order
{
    $order = new Order;
    $order->forceFill([
        'id' => 42,
        'user_id' => 1,
        'edition_id' => 2,
        'type' => OrderType::PickPurchase,
        'amount_cents' => 499,
        'currency' => 'BRL',
        'status' => OrderStatus::Pending,
        'payment_provider' => 'mercadopago',
        'checkout_idempotency_key' => '11111111-1111-4111-8111-111111111111',
        'metadata' => [],
    ]);

    return $order;
}

/** @return array<string, mixed> */
function mercadoPagoPaymentResponse(string $status = 'approved'): array
{
    return [
        'id' => 987654,
        'external_reference' => '42',
        'status' => $status,
        'status_detail' => 'accredited',
        'currency_id' => 'BRL',
        'transaction_amount' => 4.99,
        'date_approved' => '2026-07-18T12:00:00.000-03:00',
    ];
}

test('checkout preferences are server priced and use deterministic idempotency', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-07-18T12:00:00-03:00'));
    Http::preventStrayRequests();
    Http::fake([
        'https://api.mercadopago.com/checkout/preferences' => Http::response([
            'id' => 'preference-42',
            'init_point' => 'https://www.mercadopago.com.br/checkout/v1/redirect?pref_id=preference-42',
        ], 201),
    ]);

    $checkout = mercadoPagoGateway()->createCheckout(mercadoPagoOrder(), 'buyer@example.test');

    expect($checkout->providerReference)->toBe('preference-42')
        ->and($checkout->checkoutUrl)->toContain('pref_id=preference-42');
    Http::assertSent(function (Request $request): bool {
        $data = $request->data();

        return $request->method() === 'POST'
            && $request->hasHeader('Authorization', 'Bearer access-token')
            && $request->hasHeader('X-Idempotency-Key', '11111111-1111-4111-8111-111111111111')
            && $data['items'][0]['id'] === 'pick-purchase-42'
            && $data['items'][0]['currency_id'] === 'BRL'
            && $data['items'][0]['unit_price'] === 4.99
            && $data['payer']['email'] === 'buyer@example.test'
            && $data['external_reference'] === 'pick-order:42:11111111-1111-4111-8111-111111111111'
            && $data['notification_url'] === 'https://example.test/api/v1/payments/mercadopago/webhook'
            && $data['back_urls']['success'] === 'cpxsecreto://payments/success?orderId=42'
            && $data['auto_return'] === 'approved'
            && $data['binary_mode'] === true
            && $data['expires'] === true;
    });
});

test('checkout preferences reject untrusted provider redirect urls', function (string $checkoutUrl): void {
    Http::preventStrayRequests();
    Http::fake([
        'https://api.mercadopago.com/checkout/preferences' => Http::response([
            'id' => 'preference-42',
            'init_point' => $checkoutUrl,
        ], 201),
    ]);

    expect(fn () => mercadoPagoGateway()->createCheckout(mercadoPagoOrder(), 'buyer@example.test'))
        ->toThrow(UnexpectedValueException::class);
})->with([
    'plain http' => 'http://www.mercadopago.com.br/checkout',
    'javascript' => 'javascript:alert(1)',
    'custom scheme' => 'cpxsecreto://payments/checkout',
    'malformed' => 'not a checkout url',
]);

test('webhook signatures use the documented manifest and reject tampering and stale timestamps', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-07-18T12:00:00Z'));
    $timestamp = (string) now()->getTimestamp();
    $manifest = 'id:payment-abc;request-id:request-1;ts:'.$timestamp.';';
    $signature = 'ts='.$timestamp.',v1='.hash_hmac('sha256', $manifest, 'webhook-secret');
    $gateway = mercadoPagoGateway();

    $gateway->verifyWebhook($signature, 'request-1', 'PAYMENT-ABC');

    expect(fn () => $gateway->verifyWebhook($signature, 'different-request', 'PAYMENT-ABC'))
        ->toThrow(InvalidPaymentWebhook::class)
        ->and(fn () => $gateway->verifyWebhook('ts='.$timestamp.',v1=invalid', 'request-1', 'PAYMENT-ABC'))
        ->toThrow(InvalidPaymentWebhook::class);

    $staleTimestamp = (string) now()->subMinutes(10)->getTimestamp();
    $staleManifest = 'id:payment-abc;request-id:request-1;ts:'.$staleTimestamp.';';
    $staleSignature = 'ts='.$staleTimestamp.',v1='.hash_hmac('sha256', $staleManifest, 'webhook-secret');

    expect(fn () => $gateway->verifyWebhook($staleSignature, 'request-1', 'payment-abc'))
        ->toThrow(InvalidPaymentWebhook::class);
});

test('authoritative payments are mapped and refunds use idempotency before reloading payment state', function (): void {
    Http::preventStrayRequests();
    Http::fake([
        'https://api.mercadopago.com/v1/payments/987654/refunds' => Http::response([], 201),
        'https://api.mercadopago.com/v1/payments/987654' => Http::response(mercadoPagoPaymentResponse('refunded')),
    ]);

    $payment = mercadoPagoGateway()->refund('987654', 'refund-order-42');

    expect($payment->providerReference)->toBe('987654')
        ->and($payment->externalReference)->toBe('42')
        ->and($payment->status)->toBe('refunded')
        ->and($payment->amountCents)->toBe(499)
        ->and($payment->currency)->toBe('BRL')
        ->and($payment->paidAt?->toIso8601String())->toBe('2026-07-18T12:00:00-03:00');
    Http::assertSentCount(2);
    Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
        && $request->url() === 'https://api.mercadopago.com/v1/payments/987654/refunds'
        && $request->hasHeader('X-Idempotency-Key', 'refund-order-42'));
    Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
        && $request->url() === 'https://api.mercadopago.com/v1/payments/987654');
});

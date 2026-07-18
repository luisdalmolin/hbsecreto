<?php

use App\Enums\DrawConstraintSource;
use App\Enums\DrawConstraintType;
use App\Enums\EditionStatus;
use App\Enums\OrderStatus;
use App\Models\Assignment;
use App\Models\DrawConstraint;
use App\Models\Edition;
use App\Models\EditionParticipant;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Order;
use App\Models\PaymentWebhookEvent;
use App\Models\User;
use App\Services\Payments\CheckoutAttemptReference;
use App\Services\Payments\FakePaymentGateway;
use App\Services\Payments\PaymentGateway;
use Carbon\CarbonImmutable;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->buyer = User::factory()->create();
    $this->group = Group::factory()->create(['created_by' => $this->buyer->id]);
    $buyerMember = GroupMember::factory()->for($this->group)->active($this->buyer)->admin()->create();
    $receiverMember = GroupMember::factory()->for($this->group)->active()->create();
    $otherMember = GroupMember::factory()->for($this->group)->active()->create();
    $this->edition = Edition::factory()->for($this->group)->open()->create(['created_by' => $this->buyer->id]);
    $this->giver = EditionParticipant::factory()->for($this->edition)->create([
        'group_id' => $this->group->id,
        'group_member_id' => $buyerMember->id,
    ]);
    $this->receiver = EditionParticipant::factory()->for($this->edition)->create([
        'group_id' => $this->group->id,
        'group_member_id' => $receiverMember->id,
    ]);
    $this->other = EditionParticipant::factory()->for($this->edition)->create([
        'group_id' => $this->group->id,
        'group_member_id' => $otherMember->id,
    ]);
    $this->url = "/api/v1/groups/{$this->group->id}/editions/{$this->edition->id}/pick-orders";
    $gateway = app(PaymentGateway::class);

    if (! $gateway instanceof FakePaymentGateway) {
        throw new LogicException('Tests require the fake payment gateway.');
    }

    $this->gateway = $gateway;
    Sanctum::actingAs($this->buyer);
});

test('a participant creates a server priced pick purchase and owns its checkout', function (): void {
    $response = $this->postJson($this->url, ['receiverParticipantId' => $this->receiver->id]);

    $response->assertCreated()
        ->assertJsonPath('amountCents', 499)
        ->assertJsonPath('currency', 'BRL')
        ->assertJsonPath('status', 'pending')
        ->assertJsonPath('paymentProvider', 'mercadopago')
        ->assertJsonPath('checkoutUrl', 'https://sandbox.mercadopago.test/checkout/preference-1-1')
        ->assertJsonMissingPath('metadata');
    $order = Order::query()->sole();
    $constraint = DrawConstraint::query()->sole();

    expect($order->user_id)->toBe($this->buyer->id)
        ->and($order->checkout_idempotency_key)->not->toBeNull()
        ->and($order->checkout_expires_at)->not->toBeNull()
        ->and($constraint->source)->toBe(DrawConstraintSource::Purchase)
        ->and($constraint->type)->toBe(DrawConstraintType::MustPair)
        ->and($constraint->giver_edition_participant_id)->toBe($this->giver->id)
        ->and($constraint->receiver_edition_participant_id)->toBe($this->receiver->id)
        ->and($constraint->created_by)->toBeNull()
        ->and($constraint->order_id)->toBe($order->id);

    $this->postJson($this->url, ['receiverParticipantId' => $this->receiver->id])
        ->assertCreated()
        ->assertJsonPath('id', $order->id);
    expect(Order::query()->count())->toBe(1)
        ->and($this->gateway->checkoutOrderIds())->toHaveCount(1);
});

test('an abandoned checkout expires, retries with a fresh key, and stops blocking the draw', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-07-18T12:00:00Z'));
    $this->postJson($this->url, ['receiverParticipantId' => $this->receiver->id])->assertCreated();
    $order = Order::query()->sole();
    $firstKey = $order->checkout_idempotency_key;
    $firstUrl = $order->metadata['checkoutUrl'] ?? null;

    $this->travelTo(CarbonImmutable::parse('2026-07-18T12:31:00Z'));
    $this->getJson("/api/v1/groups/{$this->group->id}/editions/{$this->edition->id}/draw/preflight")
        ->assertOk();
    expect($order->refresh()->status)->toBe(OrderStatus::Failed);

    $this->postJson($this->url, ['receiverParticipantId' => $this->receiver->id])
        ->assertCreated()
        ->assertJsonPath('id', $order->id)
        ->assertJsonPath('status', 'pending');
    $order->refresh();

    expect($order->checkout_idempotency_key)->not->toBe($firstKey)
        ->and($order->metadata['checkoutUrl'] ?? null)->not->toBe($firstUrl)
        ->and($this->gateway->checkoutIdempotencyKeys())->toHaveCount(2)
        ->and($this->gateway->checkoutIdempotencyKeys()[0])->toBe($firstKey)
        ->and($this->gateway->checkoutIdempotencyKeys()[1])->toBe($order->checkout_idempotency_key);

    $this->postJson("/api/v1/groups/{$this->group->id}/editions/{$this->edition->id}/draw")
        ->assertConflict()
        ->assertJsonPath('message', 'Existe um pagamento em processamento. Aguarde a confirmação antes de realizar o sorteio.');

    $this->travelTo(CarbonImmutable::parse('2026-07-18T13:02:00Z'));
    $this->postJson("/api/v1/groups/{$this->group->id}/editions/{$this->edition->id}/draw")
        ->assertCreated();
    expect($order->refresh()->status)->toBe(OrderStatus::Failed)
        ->and(Assignment::query()->where('edition_id', $this->edition->id)->count())->toBe(3);
});

test('a live pending checkout remains idempotent and blocks preflight and draw', function (): void {
    $this->postJson($this->url, ['receiverParticipantId' => $this->receiver->id])->assertCreated();
    $order = Order::query()->sole();
    $key = $order->checkout_idempotency_key;

    $this->postJson($this->url, ['receiverParticipantId' => $this->receiver->id])
        ->assertCreated()
        ->assertJsonPath('id', $order->id);
    expect($order->refresh()->checkout_idempotency_key)->toBe($key)
        ->and($this->gateway->checkoutOrderIds())->toHaveCount(1);

    $this->getJson("/api/v1/groups/{$this->group->id}/editions/{$this->edition->id}/draw/preflight")
        ->assertConflict();
    $this->postJson("/api/v1/groups/{$this->group->id}/editions/{$this->edition->id}/draw")
        ->assertConflict();
    expect($order->refresh()->status)->toBe(OrderStatus::Pending);
});

test('a stale checkout approval is refunded without terminating the fresh attempt', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-07-18T12:00:00Z'));
    $this->postJson($this->url, ['receiverParticipantId' => $this->receiver->id])->assertCreated();
    $order = Order::query()->sole();
    $staleAttemptId = $order->checkout_idempotency_key;

    $this->travelTo(CarbonImmutable::parse('2026-07-18T12:31:00Z'));
    $this->postJson($this->url, ['receiverParticipantId' => $this->receiver->id])->assertCreated();
    $order->refresh();
    $currentAttemptId = $order->checkout_idempotency_key;
    $staleReference = new CheckoutAttemptReference($order->id, (string) $staleAttemptId);
    $this->gateway->addPayment('stale-payment', $staleReference->toString(), 'approved');

    $this->postJson(
        '/api/v1/payments/mercadopago/webhook?data_id=stale-payment',
        mercadoPagoWebhookPayload(3001, 'stale-payment'),
        mercadoPagoWebhookHeaders(),
    )->assertOk();

    expect($order->refresh()->status)->toBe(OrderStatus::Pending)
        ->and($order->checkout_idempotency_key)->toBe($currentAttemptId)
        ->and($order->checkout_idempotency_key)->not->toBe($staleAttemptId)
        ->and($this->gateway->refundIdempotencyKeys())->toBe([
            'compensate-order-'.$order->id.'-attempt-'.$staleAttemptId,
        ]);
});

test('an approval after draw is compensated idempotently before the order becomes refunded', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-07-18T12:00:00Z'));
    $this->postJson($this->url, ['receiverParticipantId' => $this->receiver->id])->assertCreated();
    $order = Order::query()->sole();
    $attemptId = $order->checkout_idempotency_key;

    $this->travelTo(CarbonImmutable::parse('2026-07-18T12:31:00Z'));
    $this->postJson("/api/v1/groups/{$this->group->id}/editions/{$this->edition->id}/draw")
        ->assertCreated();
    $assignments = Assignment::query()
        ->where('edition_id', $this->edition->id)
        ->orderBy('id')
        ->get(['giver_edition_participant_id', 'receiver_edition_participant_id'])
        ->toArray();
    $this->gateway->addPayment('late-payment', $order->id, 'approved');
    $this->gateway->failNextRefunds();
    $payload = mercadoPagoWebhookPayload(3002, 'late-payment');

    $this->postJson(
        '/api/v1/payments/mercadopago/webhook?data_id=late-payment',
        $payload,
        mercadoPagoWebhookHeaders(),
    )->assertServerError();
    expect($order->refresh()->status)->toBe(OrderStatus::Failed)
        ->and(PaymentWebhookEvent::query()->where('provider_event_id', '3002')->sole()->processed_at)->toBeNull();

    $this->postJson(
        '/api/v1/payments/mercadopago/webhook?data_id=late-payment',
        $payload,
        mercadoPagoWebhookHeaders(),
    )->assertOk();

    expect($order->refresh()->status)->toBe(OrderStatus::Refunded)
        ->and(Assignment::query()
            ->where('edition_id', $this->edition->id)
            ->orderBy('id')
            ->get(['giver_edition_participant_id', 'receiver_edition_participant_id'])
            ->toArray())->toBe($assignments)
        ->and($this->gateway->refundIdempotencyKeys())->toBe([
            'compensate-order-'.$order->id.'-attempt-'.$attemptId,
            'compensate-order-'.$order->id.'-attempt-'.$attemptId,
        ]);
});

test('purchase authorization and participant identity are enforced by the server', function (): void {
    $this->postJson($this->url, [
        'receiverParticipantId' => $this->giver->id,
    ])->assertConflict();

    $outsider = User::factory()->create();
    Sanctum::actingAs($outsider);
    $this->postJson($this->url, ['receiverParticipantId' => $this->receiver->id])->assertNotFound();

    $this->edition->update(['status' => EditionStatus::Drawn]);
    Sanctum::actingAs($this->buyer);
    $this->postJson($this->url, ['receiverParticipantId' => $this->receiver->id])->assertConflict();
    expect(Order::query()->count())->toBe(0);
});

test('buyers can list and view only their own orders without provider metadata', function (): void {
    $owned = Order::factory()->for($this->buyer)->for($this->edition)->create();
    $other = Order::factory()->for(User::factory())->for($this->edition)->create();

    $this->getJson('/api/v1/orders')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $owned->id)
        ->assertJsonMissing(['id' => $other->id])
        ->assertJsonMissingPath('data.0.metadata');
    $this->getJson("/api/v1/orders/{$owned->id}")->assertOk()->assertJsonPath('id', $owned->id);
    $this->getJson("/api/v1/orders/{$other->id}")->assertForbidden();
});

test('orders can be filtered exactly by edition and expose their purchased receiver', function (): void {
    $this->postJson($this->url, ['receiverParticipantId' => $this->receiver->id])->assertCreated();
    $order = Order::query()->sole();
    $otherEdition = Edition::factory()->for($this->group)->create(['created_by' => $this->buyer->id]);
    Order::factory()->for($this->buyer)->for($otherEdition)->create();

    $this->getJson('/api/v1/orders?filter%5Bedition_id%5D='.$this->edition->id)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $order->id)
        ->assertJsonPath('data.0.receiverParticipantId', $this->receiver->id);
    $this->getJson("/api/v1/orders/{$order->id}")
        ->assertOk()
        ->assertJsonPath('receiverParticipantId', $this->receiver->id);
});

test('a verified approved webhook is idempotent and activates the forced pair for the draw', function (): void {
    $this->postJson($this->url, ['receiverParticipantId' => $this->receiver->id])->assertCreated();
    $order = Order::query()->sole();
    $this->gateway->addPayment('1001', $order->id, 'approved', paidAt: CarbonImmutable::parse('2026-07-18T12:00:00Z'));
    $payload = mercadoPagoWebhookPayload(2001, '1001');

    $this->postJson('/api/v1/payments/mercadopago/webhook?data_id=1001', $payload, mercadoPagoWebhookHeaders())
        ->assertOk();
    $this->postJson('/api/v1/payments/mercadopago/webhook?data_id=1001', $payload, mercadoPagoWebhookHeaders())
        ->assertOk();

    expect($order->refresh()->status)->toBe(OrderStatus::Paid)
        ->and($order->paid_at?->toIso8601String())->toBe('2026-07-18T12:00:00+00:00')
        ->and(PaymentWebhookEvent::query()->count())->toBe(1)
        ->and(PaymentWebhookEvent::query()->sole()->processed_at)->not->toBeNull();

    $this->postJson("/api/v1/groups/{$this->group->id}/editions/{$this->edition->id}/draw")
        ->assertCreated();
    expect(Assignment::query()
        ->where('giver_edition_participant_id', $this->giver->id)
        ->value('receiver_edition_participant_id'))->toBe($this->receiver->id);
});

test('a duplicate approval after draw does not refund a purchase paid before draw', function (): void {
    $this->postJson($this->url, ['receiverParticipantId' => $this->receiver->id])->assertCreated();
    $order = Order::query()->sole();
    $this->gateway->addPayment('approved-payment', $order->id, 'approved');

    $this->postJson(
        '/api/v1/payments/mercadopago/webhook?data_id=approved-payment',
        mercadoPagoWebhookPayload(3101, 'approved-payment'),
        mercadoPagoWebhookHeaders(),
    )->assertOk();
    $this->postJson("/api/v1/groups/{$this->group->id}/editions/{$this->edition->id}/draw")
        ->assertCreated();
    expect(Assignment::query()
        ->where('giver_edition_participant_id', $this->giver->id)
        ->value('receiver_edition_participant_id'))->toBe($this->receiver->id);

    $this->postJson(
        '/api/v1/payments/mercadopago/webhook?data_id=approved-payment',
        mercadoPagoWebhookPayload(3102, 'approved-payment'),
        mercadoPagoWebhookHeaders(),
    )->assertOk();

    expect($order->refresh()->status)->toBe(OrderStatus::Paid)
        ->and($this->gateway->refundIdempotencyKeys())->toBe([]);
});

test('invalid or mismatched webhooks cannot mutate orders', function (): void {
    $this->postJson($this->url, ['receiverParticipantId' => $this->receiver->id])->assertCreated();
    $order = Order::query()->sole();
    $this->gateway->addPayment('1001', $order->id, 'approved');

    $this->postJson(
        '/api/v1/payments/mercadopago/webhook?data_id=1001',
        mercadoPagoWebhookPayload(2001, '1001'),
        ['x-signature' => 'invalid-signature'],
    )->assertUnauthorized();
    $this->postJson(
        '/api/v1/payments/mercadopago/webhook?data_id=1001',
        mercadoPagoWebhookPayload(2002, '1002'),
        mercadoPagoWebhookHeaders(),
    )->assertUnauthorized();

    expect($order->refresh()->status)->toBe(OrderStatus::Pending)
        ->and(PaymentWebhookEvent::query()->count())->toBe(0);
});

test('authoritative payment identity amount and currency must match the order', function (): void {
    $this->postJson($this->url, ['receiverParticipantId' => $this->receiver->id])->assertCreated();
    $order = Order::query()->sole();
    $this->gateway->addPayment('1101', $order->id, 'approved', amountCents: 1);
    $this->gateway->addPayment('1102', $order->id, 'approved', currency: 'USD');
    $this->gateway->addPayment('1103', 'not-an-order', 'approved');

    foreach (['1101', '1102', '1103'] as $index => $resourceId) {
        $this->postJson(
            "/api/v1/payments/mercadopago/webhook?data_id={$resourceId}",
            mercadoPagoWebhookPayload(2101 + $index, $resourceId),
            mercadoPagoWebhookHeaders(),
        )->assertConflict()
            ->assertJsonPath('message', 'Os dados confirmados pelo provedor não correspondem ao pedido.');
    }

    expect($order->refresh()->status)->toBe(OrderStatus::Pending)
        ->and(PaymentWebhookEvent::query()->count())->toBe(3)
        ->and(PaymentWebhookEvent::query()->whereNotNull('processed_at')->count())->toBe(0);
});

test('payment status processing is monotonic and refunds are pre-draw only', function (): void {
    $this->postJson($this->url, ['receiverParticipantId' => $this->receiver->id])->assertCreated();
    $order = Order::query()->sole();

    foreach ([
        [2001, 'rejected', OrderStatus::Failed],
        [2002, 'approved', OrderStatus::Paid],
        [2003, 'rejected', OrderStatus::Paid],
    ] as [$eventId, $providerStatus, $expectedStatus]) {
        $this->gateway->addPayment('1001', $order->id, $providerStatus);
        $this->postJson(
            '/api/v1/payments/mercadopago/webhook?data_id=1001',
            mercadoPagoWebhookPayload($eventId, '1001'),
            mercadoPagoWebhookHeaders(),
        )->assertOk();
        expect($order->refresh()->status)->toBe($expectedStatus);
    }

    $this->postJson("/api/v1/orders/{$order->id}/refund")
        ->assertCreated()
        ->assertJsonPath('status', 'refunded');
    $this->gateway->addPayment('1001', $order->id, 'approved');
    $this->postJson(
        '/api/v1/payments/mercadopago/webhook?data_id=1001',
        mercadoPagoWebhookPayload(2004, '1001'),
        mercadoPagoWebhookHeaders(),
    )->assertOk();
    expect($order->refresh()->status)->toBe(OrderStatus::Refunded);

    $this->edition->update(['status' => EditionStatus::Drawn]);
    $paid = Order::factory()->for($this->buyer)->for($this->edition)->paid()->create();
    $this->postJson("/api/v1/orders/{$paid->id}/refund")->assertConflict();
});

test('pending purchases block the draw while failed purchase constraints no longer block admin rules', function (): void {
    $this->postJson($this->url, ['receiverParticipantId' => $this->receiver->id])->assertCreated();
    $order = Order::query()->sole();

    $this->getJson("/api/v1/groups/{$this->group->id}/editions/{$this->edition->id}/draw/preflight")
        ->assertConflict()
        ->assertJsonPath('message', 'Existe um pagamento em processamento. Aguarde a confirmação antes de realizar o sorteio.');

    $this->gateway->addPayment('1001', $order->id, 'rejected');
    $this->postJson(
        '/api/v1/payments/mercadopago/webhook?data_id=1001',
        mercadoPagoWebhookPayload(2001, '1001'),
        mercadoPagoWebhookHeaders(),
    )->assertOk();

    $this->group->members()->where('user_id', $this->buyer->id)->update(['role' => 'admin']);
    $this->postJson("/api/v1/groups/{$this->group->id}/editions/{$this->edition->id}/draw-constraints", [
        'type' => 'must_pair',
        'giverParticipantId' => $this->giver->id,
        'receiverParticipantId' => $this->receiver->id,
    ])->assertCreated()->assertJsonPath('source', 'admin');
});

test('refunded purchase constraints are inactive and the purchase remains terminal', function (): void {
    $this->postJson($this->url, ['receiverParticipantId' => $this->receiver->id])->assertCreated();
    $order = Order::query()->sole();
    $this->gateway->addPayment('1001', $order->id, 'approved');
    $this->postJson(
        '/api/v1/payments/mercadopago/webhook?data_id=1001',
        mercadoPagoWebhookPayload(2001, '1001'),
        mercadoPagoWebhookHeaders(),
    )->assertOk();
    $this->postJson("/api/v1/orders/{$order->id}/refund")
        ->assertCreated()
        ->assertJsonPath('status', 'refunded');

    $this->postJson($this->url, ['receiverParticipantId' => $this->receiver->id])
        ->assertConflict();
    $this->postJson("/api/v1/groups/{$this->group->id}/editions/{$this->edition->id}/draw-constraints", [
        'type' => 'must_pair',
        'giverParticipantId' => $this->giver->id,
        'receiverParticipantId' => $this->receiver->id,
    ])->assertCreated();
    $this->postJson("/api/v1/groups/{$this->group->id}/editions/{$this->edition->id}/draw")
        ->assertCreated();

    expect(Assignment::query()
        ->where('giver_edition_participant_id', $this->giver->id)
        ->value('receiver_edition_participant_id'))->toBe($this->receiver->id);
});

/** @return array<string, mixed> */
function mercadoPagoWebhookPayload(int $eventId, string $resourceId): array
{
    return [
        'id' => $eventId,
        'type' => 'payment',
        'action' => 'payment.updated',
        'data' => ['id' => $resourceId],
    ];
}

/** @return array<string, string> */
function mercadoPagoWebhookHeaders(): array
{
    return ['x-signature' => 'valid-signature', 'x-request-id' => 'request-1'];
}

<?php

use App\Enums\DrawConstraintSource;
use App\Enums\DrawConstraintType;
use App\Models\DrawConstraint;
use App\Models\Edition;
use App\Models\EditionParticipant;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Order;
use App\Models\PaymentWebhookEvent;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('orders enforce supported values positive amounts and unique provider references', function (): void {
    $user = User::factory()->create();
    $edition = Edition::factory()->create();
    $values = [
        'user_id' => $user->id,
        'edition_id' => $edition->id,
        'type' => 'pick_purchase',
        'amount_cents' => 499,
        'currency' => 'BRL',
        'status' => 'pending',
        'payment_provider' => 'mercadopago',
        'provider_reference' => 'preference-1',
        'checkout_idempotency_key' => '11111111-1111-4111-8111-111111111111',
        'checkout_expires_at' => now()->addMinutes(30),
        'metadata' => '{}',
        'created_at' => now(),
        'updated_at' => now(),
    ];
    DB::table((new Order)->getTable())->insert($values);

    expect(fn () => DB::table((new Order)->getTable())->insert([
        ...$values,
        'provider_reference' => 'preference-2',
    ]))->toThrow(QueryException::class)
        ->and(fn () => DB::table((new Order)->getTable())->insert([
            ...$values,
            'checkout_idempotency_key' => '22222222-2222-4222-8222-222222222222',
        ]))->toThrow(QueryException::class);

    foreach ([
        ['type' => 'unknown'],
        ['status' => 'unknown'],
        ['amount_cents' => 0],
        ['currency' => 'brl'],
    ] as $invalid) {
        expect(fn () => DB::table((new Order)->getTable())->insert([
            ...$values,
            ...$invalid,
            'provider_reference' => fake()->uuid(),
            'checkout_idempotency_key' => fake()->uuid(),
        ]))->toThrow(QueryException::class);
    }
});

test('purchase constraints enforce source shape order edition identity and one purchase per giver', function (): void {
    $buyer = User::factory()->create();
    $group = Group::factory()->create(['created_by' => $buyer->id]);
    $edition = Edition::factory()->for($group)->create(['created_by' => $buyer->id]);
    $members = GroupMember::factory()->count(3)->for($group)->active()->create();
    $participants = $members->map(fn (GroupMember $member): EditionParticipant => EditionParticipant::factory()->for($edition)->create([
        'group_id' => $group->id,
        'group_member_id' => $member->id,
    ]));
    $order = Order::factory()->for($buyer)->for($edition)->create();
    $values = [
        'edition_id' => $edition->id,
        'type' => DrawConstraintType::MustPair->value,
        'giver_edition_participant_id' => $participants[0]->id,
        'receiver_edition_participant_id' => $participants[1]->id,
        'source' => DrawConstraintSource::Purchase->value,
        'order_id' => $order->id,
        'created_by' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ];
    DB::table((new DrawConstraint)->getTable())->insert($values);

    expect(fn () => DB::table((new DrawConstraint)->getTable())->insert([
        ...$values,
        'receiver_edition_participant_id' => $participants[2]->id,
    ]))->toThrow(QueryException::class)
        ->and(fn () => DB::table((new DrawConstraint)->getTable())->insert([
            ...$values,
            'source' => DrawConstraintSource::Admin->value,
            'created_by' => $buyer->id,
        ]))->toThrow(QueryException::class);

    $otherEdition = Edition::factory()->for($group)->create(['created_by' => $buyer->id]);
    $crossEditionOrder = Order::factory()->for($buyer)->for($otherEdition)->create();
    expect(fn () => DB::table((new DrawConstraint)->getTable())->insert([
        ...$values,
        'giver_edition_participant_id' => $participants[2]->id,
        'order_id' => $crossEditionOrder->id,
    ]))->toThrow(QueryException::class);
});

test('payment webhook event ids are durable idempotency keys per provider', function (): void {
    PaymentWebhookEvent::factory()->create([
        'payment_provider' => 'mercadopago',
        'provider_event_id' => 'event-1',
    ]);

    expect(fn () => PaymentWebhookEvent::factory()->create([
        'payment_provider' => 'mercadopago',
        'provider_event_id' => 'event-1',
    ]))->toThrow(QueryException::class);

    PaymentWebhookEvent::factory()->create([
        'payment_provider' => 'another-provider',
        'provider_event_id' => 'event-1',
    ]);
});

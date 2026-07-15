<?php

use App\Notifications\ExpoPush\ExpoPushContent;
use App\Notifications\ExpoPush\ExpoPushMessage;
use App\Notifications\ExpoPush\HttpExpoPushTransport;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

test('the Expo transport sends typed messages and reports invalid device tokens', function (): void {
    Http::preventStrayRequests();
    Http::fake([
        'https://exp.host/--/api/v2/push/send' => Http::response([
            'data' => [
                ['status' => 'ok', 'id' => 'ticket-1'],
                ['status' => 'error', 'message' => 'Unregistered', 'details' => ['error' => 'DeviceNotRegistered']],
            ],
        ]),
    ]);
    $content = new ExpoPushContent('Título', 'Mensagem', ['type' => 'test', 'url' => '/groups/1'], 3);
    $transport = new HttpExpoPushTransport(
        http: app(Factory::class),
        baseUrl: 'https://exp.host/--/api/v2',
        accessToken: 'access-token',
        timeout: 10,
        connectTimeout: 3,
    );

    $result = $transport->send(
        new ExpoPushMessage('ExponentPushToken[first]', $content),
        new ExpoPushMessage('ExponentPushToken[second]', $content),
    );

    expect($result->acceptedCount())->toBe(1)
        ->and($result->invalidTokens())->toBe(['ExponentPushToken[second]'])
        ->and($result->tickets[0]->ticketId)->toBe('ticket-1')
        ->and($result->tickets[1]->errorCode)->toBe('DeviceNotRegistered');
    Http::assertSent(fn (Request $request): bool => $request->hasHeader('Authorization', 'Bearer access-token')
        && $request->data()[0]['channelId'] === 'general'
        && $request->data()[0]['badge'] === 3
        && $request->data()[0]['data']['url'] === '/groups/1');
});

test('the Expo transport fetches typed delivery receipts', function (): void {
    Http::preventStrayRequests();
    Http::fake([
        'https://exp.host/--/api/v2/push/getReceipts' => Http::response([
            'data' => [
                'ticket-1' => ['status' => 'ok'],
                'ticket-2' => [
                    'status' => 'error',
                    'message' => 'Unregistered',
                    'details' => ['error' => 'DeviceNotRegistered'],
                ],
            ],
        ]),
    ]);
    $transport = new HttpExpoPushTransport(
        http: app(Factory::class),
        baseUrl: 'https://exp.host/--/api/v2',
        accessToken: '',
        timeout: 10,
        connectTimeout: 3,
    );

    $receipts = $transport->receipts('ticket-1', 'ticket-2', 'ticket-missing');

    expect($receipts)->toHaveCount(2)
        ->and($receipts['ticket-1']->delivered)->toBeTrue()
        ->and($receipts['ticket-2']->delivered)->toBeFalse()
        ->and($receipts['ticket-2']->errorCode)->toBe('DeviceNotRegistered');
    Http::assertSent(fn (Request $request): bool => $request->data() === [
        'ids' => ['ticket-1', 'ticket-2', 'ticket-missing'],
    ]);
});

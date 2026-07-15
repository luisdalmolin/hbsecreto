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
    $content = new ExpoPushContent('Título', 'Mensagem', ['type' => 'test', 'url' => '/groups/1']);
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

    expect($result->acceptedCount)->toBe(1)
        ->and($result->invalidTokens)->toBe(['ExponentPushToken[second]'])
        ->and($result->errors)->toBe(['DeviceNotRegistered']);
    Http::assertSent(fn (Request $request): bool => $request->hasHeader('Authorization', 'Bearer access-token')
        && $request->data()[0]['channelId'] === 'general'
        && $request->data()[0]['data']['url'] === '/groups/1');
});

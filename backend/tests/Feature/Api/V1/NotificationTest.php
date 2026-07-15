<?php

use App\Models\PushDevice;
use App\Models\User;
use App\Notifications\Channels\ExpoPushChannel;
use App\Notifications\ConversationMessageNotification;
use App\Notifications\ExpoPush\ExpoPushTransport;
use App\Notifications\ExpoPush\FakeExpoPushTransport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

function notificationToken(User $user, string $name = 'Test device'): string
{
    return $user->createToken($name)->plainTextToken;
}

test('notification and push device endpoints require authentication', function (): void {
    $this->getJson('/api/v1/notifications')->assertUnauthorized();
    $this->putJson('/api/v1/notifications/read')->assertUnauthorized();
    $this->withoutRequestValidation()->postJson('/api/v1/push-devices', [])->assertUnauthorized();
});

test('a push device is registered idempotently and follows the current access token', function (): void {
    $user = User::factory()->create();
    $token = notificationToken($user, 'iPhone');
    $payload = [
        'expoPushToken' => 'ExponentPushToken[abcdefghijklmnopqrstuv]',
        'platform' => 'ios',
        'deviceName' => 'iPhone de Maria',
    ];

    $first = $this->withToken($token)->postJson('/api/v1/push-devices', $payload)
        ->assertCreated()
        ->assertJsonPath('platform', 'ios')
        ->assertJsonPath('deviceName', 'iPhone de Maria');
    $second = $this->withToken($token)->postJson('/api/v1/push-devices', [
        ...$payload,
        'deviceName' => 'Novo nome',
    ])->assertCreated();

    expect($second->json('id'))->toBe($first->json('id'))
        ->and(PushDevice::query()->count())->toBe(1)
        ->and(PushDevice::query()->firstOrFail()->personal_access_token_id)->not->toBeNull();

    $this->withToken($token)->postJson('/api/v1/auth/logout')->assertNoContent();
    expect(PushDevice::query()->count())->toBe(0);
});

test('a push token moves safely to the latest authenticated account', function (): void {
    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();
    $expoToken = 'ExponentPushToken[abcdefghijklmnopqrstuv]';

    $this->withToken(notificationToken($firstUser))->postJson('/api/v1/push-devices', [
        'expoPushToken' => $expoToken,
        'platform' => 'android',
    ])->assertCreated();
    Auth::forgetGuards();
    $this->flushHeaders()->withToken(notificationToken($secondUser))->postJson('/api/v1/push-devices', [
        'expoPushToken' => $expoToken,
        'platform' => 'android',
    ])->assertCreated();

    expect(PushDevice::query()->where('expo_push_token', $expoToken)->firstOrFail()->user_id)
        ->toBe($secondUser->id);
});

test('push device registration validates Expo tokens', function (): void {
    $user = User::factory()->create();

    $this->withoutRequestValidation()
        ->withToken(notificationToken($user))
        ->postJson('/api/v1/push-devices', [
            'expoPushToken' => 'not-a-token',
            'platform' => 'web',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['expoPushToken', 'platform']);
});

test('users can list and read their durable notification inbox', function (): void {
    $user = User::factory()->create();
    $token = notificationToken($user);
    Notification::sendNow($user, new ConversationMessageNotification(1, 2, 3), ['database']);
    $notification = $user->notifications()->firstOrFail();

    $this->withToken($token)->getJson('/api/v1/notifications')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('unreadCount', 1)
        ->assertJsonPath('data.0.id', $notification->id)
        ->assertJsonPath('data.0.type', 'conversation-message')
        ->assertJsonPath('data.0.title', 'Nova mensagem')
        ->assertJsonPath('data.0.url', '/groups/1/editions/2/conversations/3')
        ->assertJsonPath('data.0.readAt', null);

    $this->withToken($token)->putJson("/api/v1/notifications/{$notification->id}/read")
        ->assertOk()
        ->assertJsonPath('readAt', fn (mixed $value): bool => is_string($value));
    expect($notification->refresh()->read_at)->not->toBeNull();

    Notification::sendNow($user, new ConversationMessageNotification(1, 2, 4), ['database']);
    $this->withToken($token)->putJson('/api/v1/notifications/read')->assertNoContent();
    expect($user->unreadNotifications()->count())->toBe(0);
});

test('Laravel delivers application notifications through the reusable Expo channel', function (): void {
    $user = User::factory()->create();
    notificationToken($user);
    PushDevice::query()->create([
        'user_id' => $user->id,
        'personal_access_token_id' => $user->tokens()->firstOrFail()->id,
        'expo_push_token' => 'ExponentPushToken[abcdefghijklmnopqrstuv]',
        'platform' => 'ios',
        'last_registered_at' => now(),
    ]);
    $notification = new ConversationMessageNotification(1, 2, 3);

    Notification::sendNow($user, $notification);

    $transport = app(ExpoPushTransport::class);
    $databaseNotification = $user->notifications()->sole();
    expect($notification->via($user))->toContain('database', ExpoPushChannel::class)
        ->and($transport)->toBeInstanceOf(FakeExpoPushTransport::class)
        ->and($transport->messages())->toHaveCount(1)
        ->and($transport->messages()[0]->content->data)->toBe([
            'notificationId' => $databaseNotification->id,
            'type' => 'conversation-message',
            'url' => '/groups/1/editions/2/conversations/3',
        ]);
});

test('users cannot mark another users notification as read', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    Notification::sendNow($owner, new ConversationMessageNotification(1, 2, 3), ['database']);
    $notification = $owner->notifications()->firstOrFail();

    $this->withToken(notificationToken($other))
        ->putJson("/api/v1/notifications/{$notification->id}/read")
        ->assertForbidden();
    expect($notification->refresh()->read_at)->toBeNull();
});

<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function (): void {
    app()->setLocale('pt_BR');
});

test('a user can sign in with a device token', function (): void {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password',
        'deviceName' => 'Ana’s iPhone',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('tokenType', 'Bearer')
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonPath('user.email', $user->email)
        ->assertJsonStructure(['accessToken']);

    expect($user->fresh()->tokens)->toHaveCount(1);
});

test('a visitor can sign up and receive a device token', function (): void {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Ana Silva',
        'email' => 'ana@example.com',
        'password' => 'password',
        'deviceName' => 'Ana’s iPhone',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('tokenType', 'Bearer')
        ->assertJsonPath('user.name', 'Ana Silva')
        ->assertJsonPath('user.email', 'ana@example.com')
        ->assertJsonStructure(['accessToken']);

    $this->assertDatabaseHas('users', [
        'name' => 'Ana Silva',
        'email' => 'ana@example.com',
    ]);
});

test('sign up prevents duplicate email addresses', function (): void {
    User::factory()->create(['email' => 'ana@example.com']);

    $this->withoutRequestValidation()->postJson('/api/v1/auth/register', [
        'name' => 'Ana Silva',
        'email' => 'ana@example.com',
        'password' => 'password',
        'deviceName' => 'Ana’s iPhone',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email'])
        ->assertJsonPath('errors.email.0', 'O campo e-mail já está em uso.');
});

test('sign in validates the request in Brazilian Portuguese', function (): void {
    // This test intentionally submits an invalid payload to exercise Laravel Data validation.
    $response = $this->withoutRequestValidation()->postJson('/api/v1/auth/login', []);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'password', 'deviceName'])
        ->assertJsonPath('errors.email.0', 'O campo e-mail é obrigatório.');
});

test('sign in rejects invalid credentials', function (): void {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'incorrect-password',
        'deviceName' => 'Ana’s iPhone',
    ]);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email'])
        ->assertJsonPath('errors.email.0', 'As credenciais informadas estão incorretas.');
});

test('an authenticated user can retrieve their profile and revoke the current token', function (): void {
    $user = User::factory()->create();
    $accessToken = $user->createToken('Ana’s iPhone');

    $this->withToken($accessToken->plainTextToken)
        ->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('id', $user->id)
        ->assertJsonPath('email', $user->email);

    $this->withToken($accessToken->plainTextToken)
        ->postJson('/api/v1/auth/logout')
        ->assertNoContent();

    $this->assertDatabaseMissing('personal_access_tokens', [
        'id' => $accessToken->accessToken->id,
    ]);
});

test('protected endpoints return a localized unauthenticated response', function (): void {
    $this->getJson('/api/v1/me')
        ->assertUnauthorized()
        ->assertJsonPath('message', 'Não autenticado.');
});

test('an authenticated user can update only their localized profile fields', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patchJson('/api/v1/me', [
            'name' => 'Ana Atualizada',
            'locale' => 'pt-BR',
            'email' => 'ignored@example.com',
        ])
        ->assertOk()
        ->assertJsonPath('name', 'Ana Atualizada')
        ->assertJsonPath('locale', 'pt-BR')
        ->assertJsonPath('email', $user->email);

    expect($user->refresh()->name)->toBe('Ana Atualizada')
        ->and($user->email)->not->toBe('ignored@example.com');
});

test('canonical user locales map to Laravel translation directories', function (): void {
    expect(User::factory()->make(['locale' => 'pt-BR'])->laravelLocale())->toBe('pt_BR')
        ->and(User::factory()->make(['locale' => 'unsupported'])->laravelLocale())->toBe('pt_BR');
});

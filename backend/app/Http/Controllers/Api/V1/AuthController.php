<?php

namespace App\Http\Controllers\Api\V1;

use App\Data\Api\V1\Auth\AuthenticationData;
use App\Data\Api\V1\Auth\LoginData;
use App\Data\Api\V1\Auth\UserData;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Authentication')]
final class AuthController extends Controller
{
    #[OA\Post(
        path: '/api/v1/auth/login',
        operationId: 'login',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/LoginRequest'),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Authenticated successfully.',
                content: new OA\JsonContent(ref: '#/components/schemas/Authentication'),
            ),
            new OA\Response(
                response: 422,
                description: 'The submitted credentials are invalid or the request payload is invalid.',
                content: new OA\JsonContent(ref: '#/components/schemas/Error'),
            ),
            new OA\Response(response: 429, description: 'Too many login attempts.'),
        ],
    )]
    public function login(LoginData $loginData): AuthenticationData
    {
        $user = User::query()->where('email', $loginData->email)->first();

        if ($user === null || ! Hash::check($loginData->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        return new AuthenticationData(
            accessToken: $user->createToken($loginData->deviceName)->plainTextToken,
            tokenType: 'Bearer',
            user: UserData::from($user),
        );
    }

    #[OA\Post(
        path: '/api/v1/auth/logout',
        operationId: 'logout',
        tags: ['Authentication'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 204, description: 'The current access token was revoked.'),
            new OA\Response(
                response: 401,
                description: 'Authentication is required.',
                content: new OA\JsonContent(ref: '#/components/schemas/Error'),
            ),
        ],
    )]
    public function logout(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $user->currentAccessToken()->delete();

        return response()->noContent();
    }

    #[OA\Get(
        path: '/api/v1/me',
        operationId: 'getCurrentUser',
        tags: ['Authentication'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'The current authenticated user.',
                content: new OA\JsonContent(ref: '#/components/schemas/User'),
            ),
            new OA\Response(
                response: 401,
                description: 'Authentication is required.',
                content: new OA\JsonContent(ref: '#/components/schemas/Error'),
            ),
        ],
    )]
    public function me(Request $request): UserData
    {
        /** @var User $user */
        $user = $request->user();

        return UserData::from($user);
    }
}

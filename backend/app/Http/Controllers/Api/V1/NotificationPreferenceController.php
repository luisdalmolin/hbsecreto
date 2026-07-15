<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Notifications\UpdateNotificationPreferences;
use App\Data\Api\V1\Notifications\NotificationPreferencesData;
use App\Data\Api\V1\Notifications\UpdateNotificationPreferencesData;
use App\Http\Controllers\Controller;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Notification preferences')]
final class NotificationPreferenceController extends Controller
{
    #[Authorize('viewAny', NotificationPreference::class)]
    #[OA\Get(
        path: '/api/v1/notification-preferences', operationId: 'getNotificationPreferences', tags: ['Notification preferences'], security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Current notification preferences.', content: new OA\JsonContent(ref: '#/components/schemas/NotificationPreferences')),
            new OA\Response(response: 401, description: 'Authentication is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function show(Request $request): NotificationPreferencesData
    {
        /** @var User $user */
        $user = $request->user();

        return NotificationPreferencesData::fromUser($user);
    }

    #[Authorize('updateAny', NotificationPreference::class)]
    #[OA\Put(
        path: '/api/v1/notification-preferences', operationId: 'updateNotificationPreferences', tags: ['Notification preferences'], security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UpdateNotificationPreferencesRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Updated notification preferences.', content: new OA\JsonContent(ref: '#/components/schemas/NotificationPreferences')),
            new OA\Response(response: 401, description: 'Authentication is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 422, description: 'The request payload is invalid.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function update(
        UpdateNotificationPreferencesData $data,
        Request $request,
        UpdateNotificationPreferences $updatePreferences,
    ): NotificationPreferencesData {
        /** @var User $user */
        $user = $request->user();
        $updatePreferences->handle($user, $data);

        return NotificationPreferencesData::fromUser($user);
    }
}

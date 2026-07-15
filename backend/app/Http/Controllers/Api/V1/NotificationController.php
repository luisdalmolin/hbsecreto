<?php

namespace App\Http\Controllers\Api\V1;

use App\Data\Api\V1\Notifications\NotificationCollectionData;
use App\Data\Api\V1\Notifications\NotificationData;
use App\Data\Api\V1\Shared\PaginationMetaData;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use OpenApi\Attributes as OA;
use Spatie\LaravelData\DataCollection;
use Spatie\QueryBuilder\QueryBuilder;

#[OA\Tag(name: 'Notifications')]
final class NotificationController extends Controller
{
    #[Authorize('viewAny', DatabaseNotification::class)]
    #[OA\Get(
        path: '/api/v1/notifications', operationId: 'listNotifications', tags: ['Notifications'], security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Authenticated user notification inbox.', content: new OA\JsonContent(ref: '#/components/schemas/NotificationCollection')),
            new OA\Response(response: 401, description: 'Authentication is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function index(Request $request): NotificationCollectionData
    {
        /** @var User $user */
        $user = $request->user();
        $unreadCount = $user->unreadNotifications()->count();
        $notifications = QueryBuilder::for($user->notifications()->getQuery()->reorder())
            ->allowedFilters('type')
            ->allowedIncludes()
            ->allowedSorts('created_at', 'read_at')
            ->allowedFields()
            ->defaultSort('-created_at')
            ->paginate();
        $items = collect($notifications->items())->map(
            fn (DatabaseNotification $notification): NotificationData => NotificationData::fromDatabaseNotification($notification),
        );
        /** @var DataCollection<int, NotificationData> $data */
        $data = NotificationData::collect($items, DataCollection::class);

        return new NotificationCollectionData(
            data: $data,
            meta: new PaginationMetaData($notifications->currentPage(), $notifications->lastPage(), $notifications->perPage(), $notifications->total()),
            unreadCount: $unreadCount,
        );
    }

    #[Authorize('update', 'notification')]
    #[OA\Put(
        path: '/api/v1/notifications/{notification}/read', operationId: 'markNotificationRead', tags: ['Notifications'], security: [['bearerAuth' => []]],
        parameters: [new OA\PathParameter(name: 'notification', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
        responses: [
            new OA\Response(response: 200, description: 'Notification marked as read.', content: new OA\JsonContent(ref: '#/components/schemas/Notification')),
            new OA\Response(response: 401, description: 'Authentication is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 403, description: 'Only the notification owner may update it.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function read(DatabaseNotification $notification): NotificationData
    {
        $notification->markAsRead();

        return NotificationData::fromDatabaseNotification($notification->refresh());
    }

    #[Authorize('viewAny', DatabaseNotification::class)]
    #[OA\Put(
        path: '/api/v1/notifications/read', operationId: 'markAllNotificationsRead', tags: ['Notifications'], security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 204, description: 'All notifications marked as read.'),
            new OA\Response(response: 401, description: 'Authentication is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function readAll(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $user->unreadNotifications()->update(['read_at' => now()]);

        return response()->noContent();
    }
}

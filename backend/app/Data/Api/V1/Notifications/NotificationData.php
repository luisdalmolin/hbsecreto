<?php

namespace App\Data\Api\V1\Notifications;

use App\Enums\AppNotificationType;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Arr;
use LogicException;
use OpenApi\Attributes as OA;
use Spatie\LaravelData\Resource;

#[OA\Schema(schema: 'Notification', required: ['id', 'type', 'title', 'body', 'url', 'readAt', 'createdAt'])]
final class NotificationData extends Resource
{
    public function __construct(
        #[OA\Property(type: 'string', format: 'uuid')] public string $id,
        #[OA\Property(type: 'string', enum: ['conversation-message', 'edition-drawn', 'edition-revealed', 'push-diagnostic'])] public AppNotificationType $type,
        #[OA\Property(example: 'Nova mensagem')] public string $title,
        #[OA\Property(example: 'Você recebeu uma nova mensagem anônima no amigo secreto.')] public string $body,
        #[OA\Property(example: '/groups/1/editions/2')] public string $url,
        #[OA\Property(type: 'string', format: 'date-time', nullable: true)] public ?string $readAt,
        #[OA\Property(type: 'string', format: 'date-time')] public string $createdAt,
    ) {}

    public static function fromDatabaseNotification(DatabaseNotification $notification): self
    {
        $type = AppNotificationType::tryFrom($notification->type);
        $title = Arr::get($notification->data, 'title');
        $body = Arr::get($notification->data, 'body');
        $url = Arr::get($notification->data, 'url');

        if ($type === null || ! is_string($title) || ! is_string($body) || ! is_string($url) || $notification->created_at === null) {
            throw new LogicException('The stored application notification payload is invalid.');
        }

        return new self(
            id: $notification->id,
            type: $type,
            title: $title,
            body: $body,
            url: $url,
            readAt: $notification->read_at?->toIso8601String(),
            createdAt: $notification->created_at->toIso8601String(),
        );
    }
}

<?php

namespace App\Notifications;

use App\Enums\AppNotificationType;
use App\Notifications\ExpoPush\ExpoPushContent;

final readonly class AppNotificationPayload
{
    public function __construct(
        public AppNotificationType $type,
        public string $title,
        public string $body,
        public string $url,
    ) {}

    /** @return array{title: string, body: string, url: string} */
    public function toDatabase(): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'url' => $this->url,
        ];
    }

    public function toExpoPush(string $notificationId): ExpoPushContent
    {
        return new ExpoPushContent(
            title: $this->title,
            body: $this->body,
            data: [
                'notificationId' => $notificationId,
                'type' => $this->type->value,
                'url' => $this->url,
            ],
        );
    }
}

<?php

namespace App\Notifications;

use App\Enums\AppNotificationType;
use App\Enums\NotificationCategory;
use App\Models\User;
use Illuminate\Support\Facades\Lang;

final class ConversationMessageNotification extends AppNotification
{
    public function __construct(
        public readonly int $groupId,
        public readonly int $editionId,
        public readonly int $conversationId,
    ) {
        parent::__construct();
    }

    public function type(): AppNotificationType
    {
        return AppNotificationType::ConversationMessage;
    }

    public function category(): NotificationCategory
    {
        return NotificationCategory::ConversationMessages;
    }

    protected function payload(User $user): AppNotificationPayload
    {
        return new AppNotificationPayload(
            type: $this->type(),
            title: Lang::get('notifications.conversation_message.title', locale: $user->preferredLocale()),
            body: Lang::get('notifications.conversation_message.body', locale: $user->preferredLocale()),
            url: "/groups/{$this->groupId}/editions/{$this->editionId}/conversations/{$this->conversationId}",
        );
    }
}

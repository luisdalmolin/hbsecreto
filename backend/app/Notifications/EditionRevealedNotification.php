<?php

namespace App\Notifications;

use App\Enums\AppNotificationType;
use App\Models\User;
use Illuminate\Support\Facades\Lang;

final class EditionRevealedNotification extends AppNotification
{
    public function __construct(
        public readonly int $groupId,
        public readonly int $editionId,
        public readonly string $editionName,
    ) {
        parent::__construct();
    }

    public function type(): AppNotificationType
    {
        return AppNotificationType::EditionRevealed;
    }

    protected function payload(User $user): AppNotificationPayload
    {
        return new AppNotificationPayload(
            type: $this->type(),
            title: Lang::get('notifications.edition_revealed.title', locale: $user->preferredLocale()),
            body: Lang::get('notifications.edition_revealed.body', ['edition' => $this->editionName], $user->preferredLocale()),
            url: "/groups/{$this->groupId}/editions/{$this->editionId}",
        );
    }
}

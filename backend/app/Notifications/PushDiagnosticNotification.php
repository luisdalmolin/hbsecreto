<?php

namespace App\Notifications;

use App\Enums\AppNotificationType;
use App\Enums\NotificationCategory;
use App\Models\User;
use Illuminate\Support\Facades\Lang;

final class PushDiagnosticNotification extends AppNotification
{
    public function type(): AppNotificationType
    {
        return AppNotificationType::PushDiagnostic;
    }

    public function category(): NotificationCategory
    {
        return NotificationCategory::System;
    }

    public function shouldSend(object $notifiable, string $channel): bool
    {
        return true;
    }

    protected function payload(User $user): AppNotificationPayload
    {
        return new AppNotificationPayload(
            type: $this->type(),
            title: Lang::get('notifications.push_diagnostic.title', locale: $user->preferredLocale()),
            body: Lang::get('notifications.push_diagnostic.body', locale: $user->preferredLocale()),
            url: '/notifications',
        );
    }
}

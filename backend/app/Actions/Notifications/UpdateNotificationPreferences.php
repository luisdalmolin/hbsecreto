<?php

namespace App\Actions\Notifications;

use App\Data\Api\V1\Notifications\UpdateNotificationPreferencesData;
use App\Enums\NotificationCategory;
use App\Models\NotificationPreference;
use App\Models\User;

final class UpdateNotificationPreferences
{
    public function handle(User $user, UpdateNotificationPreferencesData $data): void
    {
        $timestamp = now();

        NotificationPreference::query()->upsert([
            [
                'user_id' => $user->id,
                'category' => NotificationCategory::ConversationMessages->value,
                'push_enabled' => $data->conversationMessages,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'user_id' => $user->id,
                'category' => NotificationCategory::EditionUpdates->value,
                'push_enabled' => $data->editionUpdates,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
        ], ['user_id', 'category'], ['push_enabled', 'updated_at']);
    }
}

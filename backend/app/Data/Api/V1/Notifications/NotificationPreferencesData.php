<?php

namespace App\Data\Api\V1\Notifications;

use App\Enums\NotificationCategory;
use App\Models\User;
use OpenApi\Attributes as OA;
use Spatie\LaravelData\Resource;

#[OA\Schema(schema: 'NotificationPreferences', required: ['conversationMessages', 'editionUpdates'])]
final class NotificationPreferencesData extends Resource
{
    public function __construct(
        #[OA\Property(type: 'boolean', default: true)] public bool $conversationMessages,
        #[OA\Property(type: 'boolean', default: true)] public bool $editionUpdates,
    ) {}

    public static function fromUser(User $user): self
    {
        $preferences = $user->notificationPreferences()->pluck('push_enabled', 'category');

        return new self(
            conversationMessages: (bool) $preferences->get(NotificationCategory::ConversationMessages->value, true),
            editionUpdates: (bool) $preferences->get(NotificationCategory::EditionUpdates->value, true),
        );
    }
}

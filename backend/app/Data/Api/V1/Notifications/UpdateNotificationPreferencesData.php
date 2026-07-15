<?php

namespace App\Data\Api\V1\Notifications;

use OpenApi\Attributes as OA;
use Spatie\LaravelData\Data;

#[OA\Schema(schema: 'UpdateNotificationPreferencesRequest', required: ['conversationMessages', 'editionUpdates'])]
final class UpdateNotificationPreferencesData extends Data
{
    public function __construct(
        #[OA\Property(type: 'boolean')] public bool $conversationMessages,
        #[OA\Property(type: 'boolean')] public bool $editionUpdates,
    ) {}
}

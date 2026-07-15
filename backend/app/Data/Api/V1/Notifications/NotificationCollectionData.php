<?php

namespace App\Data\Api\V1\Notifications;

use App\Data\Api\V1\Shared\PaginationMetaData;
use OpenApi\Attributes as OA;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Resource;

#[OA\Schema(schema: 'NotificationCollection', required: ['data', 'meta', 'unreadCount'])]
final class NotificationCollectionData extends Resource
{
    /** @param DataCollection<int, NotificationData> $data */
    public function __construct(
        #[OA\Property(type: 'array', items: new OA\Items(ref: '#/components/schemas/Notification'))]
        public DataCollection $data,
        #[OA\Property(ref: '#/components/schemas/PaginationMeta')]
        public PaginationMetaData $meta,
        #[OA\Property(minimum: 0, example: 2)]
        public int $unreadCount,
    ) {}
}

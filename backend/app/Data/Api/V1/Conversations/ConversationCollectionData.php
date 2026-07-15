<?php

namespace App\Data\Api\V1\Conversations;

use OpenApi\Attributes as OA;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Resource;

#[OA\Schema(schema: 'ConversationCollection', required: ['data'])]
final class ConversationCollectionData extends Resource
{
    /** @param DataCollection<int, ConversationData> $data */
    public function __construct(
        #[OA\Property(type: 'array', items: new OA\Items(ref: '#/components/schemas/Conversation'))] public DataCollection $data,
    ) {}
}

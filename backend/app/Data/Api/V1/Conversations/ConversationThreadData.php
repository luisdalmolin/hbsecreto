<?php

namespace App\Data\Api\V1\Conversations;

use OpenApi\Attributes as OA;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Resource;

#[OA\Schema(schema: 'ConversationThread', required: ['conversation', 'messages'])]
final class ConversationThreadData extends Resource
{
    /** @param DataCollection<int, MessageData> $messages */
    public function __construct(
        #[OA\Property(ref: '#/components/schemas/Conversation')] public ConversationData $conversation,
        #[OA\Property(type: 'array', items: new OA\Items(ref: '#/components/schemas/Message'))] public DataCollection $messages,
    ) {}
}

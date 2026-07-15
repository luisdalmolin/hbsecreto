<?php

namespace App\Data\Api\V1\Conversations;

use OpenApi\Attributes as OA;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Data;

#[OA\Schema(schema: 'MarkConversationReadRequest', required: ['messageId'])]
final class MarkConversationReadData extends Data
{
    public function __construct(
        #[IntegerType, Min(1)] #[OA\Property(type: 'integer', nullable: true, minimum: 1)] public ?int $messageId,
    ) {}
}

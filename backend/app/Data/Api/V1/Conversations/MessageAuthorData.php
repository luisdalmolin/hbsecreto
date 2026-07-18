<?php

namespace App\Data\Api\V1\Conversations;

use OpenApi\Attributes as OA;
use Spatie\LaravelData\Resource;

#[OA\Schema(
    schema: 'MessageAuthor',
    required: ['displayName', 'anonymous'],
    properties: [
        new OA\Property(property: 'displayName', oneOf: [new OA\Schema(type: 'string'), new OA\Schema(type: 'null')], example: 'João'),
    ],
)]
final class MessageAuthorData extends Resource
{
    public function __construct(
        public ?string $displayName,
        #[OA\Property(example: false)] public bool $anonymous,
    ) {}
}

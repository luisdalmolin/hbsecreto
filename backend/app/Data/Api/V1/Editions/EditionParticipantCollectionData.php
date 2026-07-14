<?php

namespace App\Data\Api\V1\Editions;

use App\Data\Api\V1\Shared\PaginationMetaData;
use OpenApi\Attributes as OA;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Resource;

#[OA\Schema(
    schema: 'EditionParticipantCollection',
    required: ['data', 'meta', 'currentParticipantId'],
    properties: [
        new OA\Property(property: 'currentParticipantId', oneOf: [new OA\Schema(type: 'integer'), new OA\Schema(type: 'null')]),
    ],
)]
final class EditionParticipantCollectionData extends Resource
{
    /** @param DataCollection<int, EditionParticipantData> $data */
    public function __construct(
        #[OA\Property(type: 'array', items: new OA\Items(ref: '#/components/schemas/EditionParticipant'))] public DataCollection $data,
        #[OA\Property(ref: '#/components/schemas/PaginationMeta')] public PaginationMetaData $meta,
        public ?int $currentParticipantId,
    ) {}
}

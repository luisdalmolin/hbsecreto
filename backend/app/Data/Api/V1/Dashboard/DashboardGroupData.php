<?php

namespace App\Data\Api\V1\Dashboard;

use App\Enums\EditionStatus;
use OpenApi\Attributes as OA;
use Spatie\LaravelData\Resource;

#[OA\Schema(
    schema: 'DashboardGroup',
    required: ['id', 'name', 'memberCount', 'currentEditionId', 'currentEditionStatus'],
    properties: [
        new OA\Property(property: 'currentEditionId', oneOf: [new OA\Schema(type: 'integer'), new OA\Schema(type: 'null')]),
        new OA\Property(property: 'currentEditionStatus', oneOf: [new OA\Schema(type: 'string', enum: ['draft', 'open', 'drawn', 'revealed']), new OA\Schema(type: 'null')]),
    ],
)]
final class DashboardGroupData extends Resource
{
    public function __construct(
        #[OA\Property(example: 1)] public int $id,
        #[OA\Property(example: 'Família Silva')] public string $name,
        #[OA\Property(minimum: 1, example: 8)] public int $memberCount,
        public ?int $currentEditionId,
        public ?EditionStatus $currentEditionStatus,
    ) {}
}

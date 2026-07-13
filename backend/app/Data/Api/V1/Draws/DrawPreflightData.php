<?php

namespace App\Data\Api\V1\Draws;

use OpenApi\Attributes as OA;
use Spatie\LaravelData\Resource;

#[OA\Schema(schema: 'DrawPreflight', required: ['ready', 'participantCount'])]
final class DrawPreflightData extends Resource
{
    public function __construct(
        #[OA\Property(example: true)] public bool $ready,
        #[OA\Property(minimum: 2, example: 8)] public int $participantCount,
    ) {}
}

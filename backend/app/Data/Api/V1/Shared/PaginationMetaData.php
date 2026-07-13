<?php

namespace App\Data\Api\V1\Shared;

use OpenApi\Attributes as OA;
use Spatie\LaravelData\Resource;

#[OA\Schema(schema: 'PaginationMeta', required: ['currentPage', 'lastPage', 'perPage', 'total'])]
final class PaginationMetaData extends Resource
{
    public function __construct(
        #[OA\Property(example: 1)]
        public int $currentPage,
        #[OA\Property(example: 1)]
        public int $lastPage,
        #[OA\Property(example: 15)]
        public int $perPage,
        #[OA\Property(example: 1)]
        public int $total,
    ) {}
}

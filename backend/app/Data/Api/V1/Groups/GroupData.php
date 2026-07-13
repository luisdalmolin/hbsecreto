<?php

namespace App\Data\Api\V1\Groups;

use OpenApi\Attributes as OA;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Resource;

#[OA\Schema(schema: 'Group', required: ['id', 'name', 'createdBy'])]
final class GroupData extends Resource
{
    public function __construct(
        #[OA\Property(example: 1)]
        public int $id,
        #[OA\Property(example: 'Família Silva')]
        public string $name,
        #[OA\Property(nullable: true, example: 'Amigo secreto anual da família.')]
        public ?string $description,
        #[MapInputName('created_by')]
        #[OA\Property(example: 1)]
        public int $createdBy,
    ) {}
}

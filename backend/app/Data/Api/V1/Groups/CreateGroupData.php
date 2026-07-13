<?php

namespace App\Data\Api\V1\Groups;

use OpenApi\Attributes as OA;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Data;

#[OA\Schema(schema: 'CreateGroupRequest', required: ['name'])]
final class CreateGroupData extends Data
{
    public function __construct(
        #[Max(255)]
        #[OA\Property(maxLength: 255, example: 'Família Silva')]
        public string $name,
        #[Max(5000)]
        #[OA\Property(maxLength: 5000, nullable: true, example: 'Amigo secreto anual da família.')]
        public ?string $description = null,
    ) {}
}

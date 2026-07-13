<?php

namespace App\Data\Api\V1\Groups;

use OpenApi\Attributes as OA;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

#[OA\Schema(schema: 'UpdateGroupRequest')]
final class UpdateGroupData extends Data
{
    public function __construct(
        #[Max(255)] #[OA\Property(type: 'string', maxLength: 255)] public string|Optional $name,
        #[Max(5000)] #[OA\Property(type: 'string', maxLength: 5000, nullable: true)] public string|Optional|null $description,
    ) {}
}

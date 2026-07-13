<?php

namespace App\Data\Api\V1\Groups;

use App\Enums\GroupMemberRole;
use OpenApi\Attributes as OA;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

#[OA\Schema(schema: 'UpdateGroupMemberRequest')]
final class UpdateGroupMemberData extends Data
{
    public function __construct(
        #[Max(255)]
        #[OA\Property(type: 'string', maxLength: 255, nullable: true, example: 'João')]
        public string|Optional|null $displayName,
        #[Email]
        #[OA\Property(type: 'string', format: 'email', nullable: true, example: 'joao@example.com')]
        public string|Optional|null $email,
        #[OA\Property(type: 'string', enum: ['admin', 'member'], example: 'member')]
        public GroupMemberRole|Optional $role,
    ) {}
}

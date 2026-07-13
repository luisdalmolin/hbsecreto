<?php

namespace App\Data\Api\V1\Groups;

use App\Enums\GroupMemberRole;
use OpenApi\Attributes as OA;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Data;

#[OA\Schema(schema: 'CreateGroupMemberRequest', required: ['displayName'])]
final class CreateGroupMemberData extends Data
{
    public function __construct(
        #[Max(255)]
        #[OA\Property(maxLength: 255, example: 'João Silva')]
        public string $displayName,
        #[Email]
        #[OA\Property(format: 'email', nullable: true, example: 'joao@example.com')]
        public ?string $email = null,
        #[OA\Property(type: 'string', enum: ['admin', 'member'], example: 'member')]
        public GroupMemberRole $role = GroupMemberRole::Member,
    ) {}
}

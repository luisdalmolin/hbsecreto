<?php

namespace App\Data\Api\V1\Groups;

use OpenApi\Attributes as OA;
use Spatie\LaravelData\Resource;

#[OA\Schema(schema: 'IssuedInvitation', required: ['member', 'inviteToken', 'expiresAt'])]
final class IssuedInvitationData extends Resource
{
    public function __construct(
        #[OA\Property(ref: '#/components/schemas/GroupMember')] public GroupMemberData $member,
        #[OA\Property(example: 'c4c2bfa...')] public string $inviteToken,
        #[OA\Property(format: 'date-time')] public string $expiresAt,
    ) {}
}

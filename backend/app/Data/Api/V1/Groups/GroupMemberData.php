<?php

namespace App\Data\Api\V1\Groups;

use App\Enums\GroupMemberRole;
use App\Enums\GroupMemberStatus;
use App\Models\GroupMember;
use App\Models\User;
use OpenApi\Attributes as OA;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Resource;

#[OA\Schema(schema: 'GroupMember', required: ['id', 'groupId', 'displayName', 'role', 'status'])]
final class GroupMemberData extends Resource
{
    public function __construct(
        #[OA\Property(example: 1)] public int $id,
        #[MapInputName('group_id')]
        #[OA\Property(example: 1)] public int $groupId,
        #[MapInputName('user_id')]
        #[OA\Property(nullable: true, example: 1, description: 'Visible only to this member and active group administrators.')] public ?int $userId,
        #[MapInputName('display_name')]
        #[OA\Property(nullable: true, example: 'João')] public ?string $displayName,
        #[OA\Property(format: 'email', nullable: true, example: 'joao@example.com', description: 'Visible only to this member and active group administrators.')] public ?string $email,
        #[OA\Property(type: 'string', enum: ['admin', 'member'])] public GroupMemberRole $role,
        #[OA\Property(type: 'string', enum: ['invited', 'active', 'inactive'])] public GroupMemberStatus $status,
    ) {}

    public static function forViewer(GroupMember $member, User $viewer, bool $viewerIsAdmin): self
    {
        $canSeePrivateFields = $viewerIsAdmin || $member->user_id === $viewer->id;

        return new self(
            id: $member->id,
            groupId: $member->group_id,
            userId: $canSeePrivateFields ? $member->user_id : null,
            displayName: $member->display_name,
            email: $canSeePrivateFields ? $member->email : null,
            role: $member->role,
            status: $member->status,
        );
    }
}

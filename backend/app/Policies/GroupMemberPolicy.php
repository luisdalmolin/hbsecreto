<?php

namespace App\Policies;

use App\Enums\GroupMemberRole;
use App\Enums\GroupMemberStatus;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;

final class GroupMemberPolicy
{
    public function viewAny(User $user, Group $group): bool
    {
        return $this->isActiveMember($user, $group);
    }

    public function create(User $user, Group $group): bool
    {
        return $this->isActiveAdmin($user, $group);
    }

    public function update(User $user, GroupMember $member): bool
    {
        return $member->user_id === $user->id || $this->isActiveAdmin($user, $member->group()->firstOrFail());
    }

    public function deactivate(User $user, GroupMember $member): bool
    {
        return $member->user_id === $user->id || $this->isActiveAdmin($user, $member->group()->firstOrFail());
    }

    public function reactivate(User $user, GroupMember $member): bool
    {
        return $this->isActiveAdmin($user, $member->group()->firstOrFail());
    }

    public function invite(User $user, GroupMember $member): bool
    {
        return $this->isActiveAdmin($user, $member->group()->firstOrFail());
    }

    private function isActiveMember(User $user, Group $group): bool
    {
        return $group->members()->whereBelongsTo($user)->where('status', GroupMemberStatus::Active)->exists();
    }

    private function isActiveAdmin(User $user, Group $group): bool
    {
        return $group->members()
            ->whereBelongsTo($user)
            ->where('status', GroupMemberStatus::Active)
            ->where('role', GroupMemberRole::Admin)
            ->exists();
    }
}

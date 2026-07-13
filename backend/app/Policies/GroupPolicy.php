<?php

namespace App\Policies;

use App\Enums\GroupMemberRole;
use App\Enums\GroupMemberStatus;
use App\Models\Group;
use App\Models\User;

final class GroupPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, Group $group): bool
    {
        return $this->activeMemberExists($user, $group);
    }

    public function update(User $user, Group $group): bool
    {
        return $this->activeAdminExists($user, $group);
    }

    public function delete(User $user, Group $group): bool
    {
        return $this->activeAdminExists($user, $group);
    }

    private function activeMemberExists(User $user, Group $group): bool
    {
        return $group->members()
            ->whereBelongsTo($user)
            ->where('status', GroupMemberStatus::Active)
            ->exists();
    }

    private function activeAdminExists(User $user, Group $group): bool
    {
        return $group->members()
            ->whereBelongsTo($user)
            ->where('status', GroupMemberStatus::Active)
            ->where('role', GroupMemberRole::Admin)
            ->exists();
    }
}

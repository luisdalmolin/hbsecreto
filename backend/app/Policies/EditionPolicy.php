<?php

namespace App\Policies;

use App\Enums\GroupMemberRole;
use App\Enums\GroupMemberStatus;
use App\Models\Edition;
use App\Models\Group;
use App\Models\User;

final class EditionPolicy
{
    public function viewAny(User $user, Group $group): bool
    {
        return $this->isActiveMember($user, $group);
    }

    public function create(User $user, Group $group): bool
    {
        return $this->isActiveAdmin($user, $group);
    }

    public function view(User $user, Edition $edition): bool
    {
        return $this->isActiveMember($user, $edition->group()->firstOrFail());
    }

    public function update(User $user, Edition $edition): bool
    {
        return $this->isActiveAdmin($user, $edition->group()->firstOrFail());
    }

    public function delete(User $user, Edition $edition): bool
    {
        return $this->isActiveAdmin($user, $edition->group()->firstOrFail());
    }

    public function transition(User $user, Edition $edition): bool
    {
        return $this->isActiveAdmin($user, $edition->group()->firstOrFail());
    }

    public function manageDraw(User $user, Edition $edition): bool
    {
        return $this->isActiveAdmin($user, $edition->group()->firstOrFail());
    }

    public function viewOwnAssignment(User $user, Edition $edition): bool
    {
        return $this->isActiveParticipant($user, $edition);
    }

    public function viewAssignments(User $user, Edition $edition): bool
    {
        return $this->isActiveAdmin($user, $edition->group()->firstOrFail())
            || $this->isActiveParticipant($user, $edition);
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

    private function isActiveParticipant(User $user, Edition $edition): bool
    {
        return $edition->participants()
            ->whereHas('groupMember', fn ($members) => $members
                ->whereBelongsTo($user)
                ->where('status', GroupMemberStatus::Active))
            ->exists();
    }
}

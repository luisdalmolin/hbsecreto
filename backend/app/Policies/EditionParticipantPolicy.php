<?php

namespace App\Policies;

use App\Enums\GroupMemberRole;
use App\Enums\GroupMemberStatus;
use App\Models\Edition;
use App\Models\EditionParticipant;
use App\Models\User;

final class EditionParticipantPolicy
{
    public function viewAny(User $user, Edition $edition): bool
    {
        return $edition->group()->firstOrFail()->members()->whereBelongsTo($user)->where('status', GroupMemberStatus::Active)->exists();
    }

    public function create(User $user, Edition $edition): bool
    {
        return $this->isActiveAdmin($user, $edition);
    }

    public function delete(User $user, EditionParticipant $participant): bool
    {
        return $this->isActiveAdmin($user, $participant->edition()->firstOrFail());
    }

    private function isActiveAdmin(User $user, Edition $edition): bool
    {
        return $edition->group()->firstOrFail()->members()
            ->whereBelongsTo($user)
            ->where('status', GroupMemberStatus::Active)
            ->where('role', GroupMemberRole::Admin)
            ->exists();
    }
}

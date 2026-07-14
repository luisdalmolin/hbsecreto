<?php

namespace App\Policies;

use App\Enums\GroupMemberStatus;
use App\Models\Assignment;
use App\Models\Edition;
use App\Models\User;
use App\Models\Wish;

final class WishPolicy
{
    public function viewOwn(User $user, Edition $edition): bool
    {
        return $this->participatesAs($user, $edition);
    }

    public function view(User $user, Wish $wish): bool
    {
        if ($this->owns($user, $wish)) {
            return true;
        }

        return Assignment::query()
            ->where('receiver_edition_participant_id', $wish->edition_participant_id)
            ->whereHas('giver.groupMember', fn ($members) => $members
                ->whereBelongsTo($user)
                ->where('status', GroupMemberStatus::Active))
            ->exists();
    }

    public function create(User $user, Edition $edition): bool
    {
        return $this->participatesAs($user, $edition);
    }

    public function update(User $user, Wish $wish): bool
    {
        return $this->owns($user, $wish);
    }

    public function delete(User $user, Wish $wish): bool
    {
        return $this->owns($user, $wish);
    }

    public function reorder(User $user, Edition $edition): bool
    {
        return $this->participatesAs($user, $edition);
    }

    private function participatesAs(User $user, Edition $edition): bool
    {
        return $edition->participants()
            ->whereHas('groupMember', fn ($members) => $members
                ->whereBelongsTo($user)
                ->where('status', GroupMemberStatus::Active))
            ->exists();
    }

    private function owns(User $user, Wish $wish): bool
    {
        return $wish->editionParticipant()
            ->whereHas('groupMember', fn ($members) => $members
                ->whereBelongsTo($user)
                ->where('status', GroupMemberStatus::Active))
            ->exists();
    }
}

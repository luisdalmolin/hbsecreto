<?php

namespace App\Policies;

use App\Enums\GroupMemberStatus;
use App\Models\Conversation;
use App\Models\Edition;
use App\Models\User;

final class ConversationPolicy
{
    public function viewAny(User $user, Edition $edition): bool
    {
        return $edition->participants()
            ->whereHas('groupMember', fn ($members) => $members
                ->whereBelongsTo($user)
                ->where('status', GroupMemberStatus::Active))
            ->exists();
    }

    public function view(User $user, Conversation $conversation): bool
    {
        return $conversation->assignment()
            ->where(function ($assignments) use ($user): void {
                $assignments
                    ->whereHas('giver.groupMember', fn ($members) => $members
                        ->whereBelongsTo($user)
                        ->where('status', GroupMemberStatus::Active))
                    ->orWhereHas('receiver.groupMember', fn ($members) => $members
                        ->whereBelongsTo($user)
                        ->where('status', GroupMemberStatus::Active));
            })
            ->exists();
    }

    public function send(User $user, Conversation $conversation): bool
    {
        return $this->view($user, $conversation);
    }
}

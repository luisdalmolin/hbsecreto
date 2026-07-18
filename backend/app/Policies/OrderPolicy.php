<?php

namespace App\Policies;

use App\Enums\GroupMemberStatus;
use App\Models\Edition;
use App\Models\Order;
use App\Models\User;

final class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Order $order): bool
    {
        return $order->user_id === $user->id;
    }

    public function create(User $user, Edition $edition): bool
    {
        return $edition->participants()
            ->whereHas('groupMember', fn ($members) => $members
                ->whereBelongsTo($user)
                ->where('status', GroupMemberStatus::Active))
            ->exists();
    }

    public function refund(User $user, Order $order): bool
    {
        return $this->view($user, $order);
    }
}

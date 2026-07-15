<?php

namespace App\Policies;

use App\Models\User;

final class NotificationPreferencePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function updateAny(User $user): bool
    {
        return true;
    }
}

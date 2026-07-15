<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;

final class DatabaseNotificationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function update(User $user, DatabaseNotification $notification): bool
    {
        return $notification->notifiable_type === 'user'
            && (int) $notification->notifiable_id === $user->id;
    }
}

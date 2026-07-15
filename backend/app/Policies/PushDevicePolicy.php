<?php

namespace App\Policies;

use App\Models\PushDevice;
use App\Models\User;

final class PushDevicePolicy
{
    public function create(User $user): bool
    {
        return true;
    }

    public function delete(User $user, PushDevice $pushDevice): bool
    {
        return $pushDevice->user_id === $user->id;
    }
}

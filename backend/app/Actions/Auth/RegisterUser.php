<?php

namespace App\Actions\Auth;

use App\Data\Api\V1\Auth\RegisterData;
use App\Models\User;

final class RegisterUser
{
    public function handle(RegisterData $registerData): User
    {
        return User::query()->create([
            'name' => $registerData->name,
            'email' => $registerData->email,
            'password' => $registerData->password,
        ]);
    }
}

<?php

namespace App\Actions\Groups;

use App\Enums\GroupMemberRole;
use App\Enums\GroupMemberStatus;
use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class CreateGroup
{
    public function handle(User $creator, string $name, ?string $description): Group
    {
        return DB::transaction(function () use ($creator, $name, $description): Group {
            $group = Group::query()->create([
                'name' => $name,
                'description' => $description,
                'created_by' => $creator->id,
            ]);

            $group->members()->create([
                'user_id' => $creator->id,
                'display_name' => $creator->name,
                'email' => $creator->email,
                'role' => GroupMemberRole::Admin,
                'status' => GroupMemberStatus::Active,
                'joined_at' => now(),
            ]);

            return $group;
        });
    }
}

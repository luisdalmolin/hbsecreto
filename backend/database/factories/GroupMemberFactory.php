<?php

namespace Database\Factories;

use App\Enums\GroupMemberRole;
use App\Enums\GroupMemberStatus;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<GroupMember> */
class GroupMemberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'group_id' => Group::factory(),
            'user_id' => null,
            'display_name' => fake()->name(),
            'email' => fake()->optional()->safeEmail(),
            'role' => GroupMemberRole::Member,
            'status' => GroupMemberStatus::Invited,
        ];
    }

    public function active(?User $user = null): static
    {
        return $this->state(fn (): array => [
            'user_id' => $user ?? User::factory(),
            'status' => GroupMemberStatus::Active,
            'joined_at' => now(),
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (): array => ['role' => GroupMemberRole::Admin]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['status' => GroupMemberStatus::Inactive]);
    }
}

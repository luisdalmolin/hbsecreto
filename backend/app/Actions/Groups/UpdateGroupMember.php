<?php

namespace App\Actions\Groups;

use App\Enums\GroupMemberRole;
use App\Enums\GroupMemberStatus;
use App\Exceptions\DomainConflictException;
use App\Models\GroupMember;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelData\Optional;

final class UpdateGroupMember
{
    public function handle(
        User $actor,
        GroupMember $member,
        string|Optional|null $displayName,
        string|Optional|null $email,
        GroupMemberRole|Optional $role,
    ): GroupMember {
        return DB::transaction(function () use ($actor, $member, $displayName, $email, $role): GroupMember {
            $lockedMember = GroupMember::query()->lockForUpdate()->findOrFail($member->id);
            $changes = [];

            if (! $displayName instanceof Optional) {
                $changes['display_name'] = $displayName;
            }

            if (! $email instanceof Optional || ! $role instanceof Optional) {
                if (! $actor->can('invite', $lockedMember)) {
                    throw new AuthorizationException;
                }
            }

            if (! $email instanceof Optional) {
                $changes['email'] = $email;
            }

            if (! $role instanceof Optional && $role !== $lockedMember->role) {
                if ($lockedMember->role === GroupMemberRole::Admin && $role === GroupMemberRole::Member) {
                    $this->ensureAnotherClaimedActiveAdmin($lockedMember);
                }

                $changes['role'] = $role;
            }

            $lockedMember->update($changes);

            return $lockedMember->refresh();
        });
    }

    private function ensureAnotherClaimedActiveAdmin(GroupMember $member): void
    {
        $group = $member->group()->firstOrFail();
        $adminCount = GroupMember::query()
            ->whereBelongsTo($group)
            ->where('role', GroupMemberRole::Admin)
            ->where('status', GroupMemberStatus::Active)
            ->whereNotNull('user_id')
            ->lockForUpdate()
            ->count();

        if ($adminCount <= 1) {
            throw new DomainConflictException('groups.last_admin');
        }
    }
}

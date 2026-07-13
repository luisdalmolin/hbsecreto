<?php

namespace App\Actions\Groups;

use App\Enums\EditionStatus;
use App\Enums\GroupMemberRole;
use App\Enums\GroupMemberStatus;
use App\Exceptions\DomainConflictException;
use App\Models\GroupMember;
use Illuminate\Support\Facades\DB;

final class DeactivateGroupMember
{
    public function handle(GroupMember $member): GroupMember
    {
        return DB::transaction(function () use ($member): GroupMember {
            $lockedMember = GroupMember::query()->lockForUpdate()->findOrFail($member->id);

            if ($lockedMember->status === GroupMemberStatus::Inactive) {
                return $lockedMember;
            }

            if ($lockedMember->editionParticipants()->whereHas(
                'edition',
                fn ($query) => $query->whereIn('status', [EditionStatus::Draft, EditionStatus::Open]),
            )->exists()) {
                throw new DomainConflictException('groups.member_has_editable_roster');
            }

            if ($lockedMember->role === GroupMemberRole::Admin && $lockedMember->user_id !== null) {
                $group = $lockedMember->group()->firstOrFail();
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

            $lockedMember->update(['status' => GroupMemberStatus::Inactive]);

            return $lockedMember->refresh();
        });
    }
}

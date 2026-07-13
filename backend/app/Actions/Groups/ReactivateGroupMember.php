<?php

namespace App\Actions\Groups;

use App\Enums\GroupMemberStatus;
use App\Models\GroupMember;
use Illuminate\Support\Facades\DB;

final class ReactivateGroupMember
{
    public function handle(GroupMember $member): GroupMember
    {
        return DB::transaction(function () use ($member): GroupMember {
            $lockedMember = GroupMember::query()->lockForUpdate()->findOrFail($member->id);
            $lockedMember->update([
                'status' => $lockedMember->user_id === null ? GroupMemberStatus::Invited : GroupMemberStatus::Active,
            ]);

            return $lockedMember->refresh();
        });
    }
}

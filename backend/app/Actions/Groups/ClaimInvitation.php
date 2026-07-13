<?php

namespace App\Actions\Groups;

use App\Enums\GroupMemberStatus;
use App\Exceptions\DomainConflictException;
use App\Models\GroupMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ClaimInvitation
{
    public function __construct(private readonly FindInvitation $findInvitation) {}

    public function handle(string $token, User $user): GroupMember
    {
        return DB::transaction(function () use ($token, $user): GroupMember {
            $member = $this->findInvitation->handle($token, lock: true)->member;

            if ($member->email !== null && Str::lower($member->email) !== Str::lower($user->email)) {
                throw new DomainConflictException('groups.invitation_email_mismatch');
            }

            $group = $member->group()->firstOrFail();

            if (GroupMember::query()->whereBelongsTo($group)->whereBelongsTo($user)->exists()) {
                throw new DomainConflictException('groups.membership_already_exists');
            }

            $member->update([
                'user_id' => $user->id,
                'status' => GroupMemberStatus::Active,
                'joined_at' => now(),
                'invite_token' => null,
                'invite_expires_at' => null,
            ]);

            return $member->refresh();
        });
    }
}

<?php

namespace App\Actions\Groups;

use App\Enums\GroupMemberStatus;
use App\Exceptions\DomainConflictException;
use App\Models\GroupMember;
use Illuminate\Support\Carbon;

final class IssueInvitation
{
    public function handle(GroupMember $member): IssuedInvitation
    {
        if ($member->user_id !== null) {
            throw new DomainConflictException('groups.member_already_claimed');
        }

        $token = bin2hex(random_bytes(32));
        $expiresAt = Carbon::now()->addDays(7);
        $member->update([
            'invite_token' => hash('sha256', $token),
            'invite_expires_at' => $expiresAt,
            'status' => GroupMemberStatus::Invited,
        ]);

        return new IssuedInvitation($member->refresh(), $token, $expiresAt);
    }
}

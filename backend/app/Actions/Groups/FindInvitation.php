<?php

namespace App\Actions\Groups;

use App\Models\GroupMember;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class FindInvitation
{
    public function handle(string $token, bool $lock = false): ValidInvitation
    {
        $query = GroupMember::query()
            ->with('group')
            ->where('invite_token', hash('sha256', $token))
            ->whereNull('user_id')
            ->where('invite_expires_at', '>', now());

        if ($lock) {
            $query->lockForUpdate();
        }

        $member = $query->first();

        if ($member === null || $member->invite_expires_at === null) {
            throw (new ModelNotFoundException)->setModel(GroupMember::class);
        }

        return new ValidInvitation($member, $member->invite_expires_at);
    }
}

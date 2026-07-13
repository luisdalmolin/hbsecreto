<?php

namespace App\Actions\Groups;

use App\Models\GroupMember;
use Illuminate\Support\Carbon;

final readonly class IssuedInvitation
{
    public function __construct(
        public GroupMember $member,
        public string $token,
        public Carbon $expiresAt,
    ) {}
}

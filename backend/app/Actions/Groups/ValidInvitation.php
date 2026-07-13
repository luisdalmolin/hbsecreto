<?php

namespace App\Actions\Groups;

use App\Models\GroupMember;
use Carbon\CarbonInterface;

final readonly class ValidInvitation
{
    public function __construct(
        public GroupMember $member,
        public CarbonInterface $expiresAt,
    ) {}
}

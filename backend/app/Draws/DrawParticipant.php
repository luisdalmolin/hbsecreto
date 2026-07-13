<?php

namespace App\Draws;

final readonly class DrawParticipant
{
    public function __construct(
        public int $id,
        public int $groupMemberId,
    ) {}
}

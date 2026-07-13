<?php

namespace App\Draws;

final readonly class HistoricalPair
{
    public function __construct(
        public int $giverGroupMemberId,
        public int $receiverGroupMemberId,
        public int $penalty,
    ) {}
}

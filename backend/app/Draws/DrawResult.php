<?php

namespace App\Draws;

final readonly class DrawResult
{
    /** @param list<DrawPair> $pairs */
    public function __construct(
        public array $pairs,
        public int $totalPenalty,
    ) {}
}

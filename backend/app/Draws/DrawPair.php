<?php

namespace App\Draws;

final readonly class DrawPair
{
    public function __construct(
        public int $giverParticipantId,
        public int $receiverParticipantId,
    ) {}
}

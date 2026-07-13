<?php

namespace App\Draws;

use App\Enums\DrawConstraintType;

final readonly class DrawConstraintInput
{
    public function __construct(
        public DrawConstraintType $type,
        public int $giverParticipantId,
        public int $receiverParticipantId,
    ) {}
}

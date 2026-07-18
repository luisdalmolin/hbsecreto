<?php

namespace App\Actions\DrawConstraints;

use App\Models\DrawConstraint;
use Illuminate\Database\Eloquent\Collection;

final readonly class CopiedDrawConstraints
{
    /** @param Collection<int, DrawConstraint> $constraints */
    public function __construct(
        public ?int $sourceEditionId,
        public Collection $constraints,
        public int $skippedMissingParticipants,
        public int $skippedDuplicates,
        public int $skippedConflicts,
    ) {}
}

<?php

namespace App\Draws;

final readonly class DrawInput
{
    /**
     * @param  list<DrawParticipant>  $participants
     * @param  list<DrawConstraintInput>  $constraints
     * @param  list<HistoricalPair>  $history
     */
    public function __construct(
        public array $participants,
        public array $constraints,
        public array $history,
        public int $seed,
    ) {}
}

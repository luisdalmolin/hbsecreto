<?php

namespace App\Actions\Draws;

use App\Draws\DrawConstraintInput;
use App\Draws\DrawInput;
use App\Draws\DrawParticipant;
use App\Draws\EditionTypeRegistry;
use App\Draws\HistoricalPair;
use App\Enums\EditionStatus;
use App\Models\Assignment;
use App\Models\DrawConstraint;
use App\Models\Edition;
use App\Models\EditionParticipant;
use Illuminate\Database\Eloquent\Collection;

final readonly class BuildDrawInput
{
    public function __construct(private EditionTypeRegistry $registry) {}

    /**
     * @param  Collection<int, EditionParticipant>  $participants
     * @param  Collection<int, DrawConstraint>  $constraints
     */
    public function handle(Edition $edition, Collection $participants, Collection $constraints, int $seed): DrawInput
    {
        $drawParticipants = [];

        foreach ($participants as $participant) {
            $drawParticipants[] = new DrawParticipant($participant->id, $participant->group_member_id);
        }

        $drawConstraints = [];

        foreach ($constraints as $constraint) {
            $drawConstraints[] = new DrawConstraintInput(
                $constraint->type,
                $constraint->giver_edition_participant_id,
                $constraint->receiver_edition_participant_id,
            );
        }

        return new DrawInput($drawParticipants, $drawConstraints, $this->history($edition), $seed);
    }

    /** @return list<HistoricalPair> */
    private function history(Edition $edition): array
    {
        $depth = $this->registry->historyLookbackDepth($edition->settings);

        if ($depth === 0) {
            return [];
        }

        $historicalEditions = Edition::query()
            ->where('group_id', $edition->group_id)
            ->whereKeyNot($edition->id)
            ->whereIn('status', [EditionStatus::Drawn, EditionStatus::Revealed, EditionStatus::Archived])
            ->whereNotNull('drawn_at')
            ->orderByDesc('drawn_at')
            ->orderByDesc('id')
            ->limit($depth)
            ->get(['id']);
        $history = [];

        foreach ($historicalEditions as $rank => $historicalEdition) {
            $penalty = $depth - $rank;
            $assignments = Assignment::query()
                ->whereBelongsTo($historicalEdition)
                ->with(['giver:id,group_member_id', 'receiver:id,group_member_id'])
                ->get();

            foreach ($assignments as $assignment) {
                $giver = $assignment->giver;
                $receiver = $assignment->receiver;

                if ($giver === null || $receiver === null) {
                    throw new \LogicException('Historical assignments must reference both participants.');
                }

                $history[] = new HistoricalPair(
                    $giver->group_member_id,
                    $receiver->group_member_id,
                    $penalty,
                );
            }
        }

        return $history;
    }
}

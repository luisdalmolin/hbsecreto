<?php

namespace App\Actions\Draws;

use App\Actions\Conversations\CreateAssignmentConversations;
use App\Actions\Notifications\NotifyEditionParticipants;
use App\Draws\DrawConflictException;
use App\Draws\DrawFailure;
use App\Draws\DrawFailureCode;
use App\Draws\EditionTypeRegistry;
use App\Enums\EditionStatus;
use App\Models\Assignment;
use App\Models\DrawConstraint;
use App\Models\Edition;
use App\Models\EditionParticipant;
use App\Models\Group;
use App\Notifications\EditionDrawnNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final readonly class PerformDraw
{
    public function __construct(
        private BuildDrawInput $buildInput,
        private EditionTypeRegistry $registry,
        private CreateAssignmentConversations $createConversations,
        private NotifyEditionParticipants $notifyParticipants,
    ) {}

    public function handle(Edition $edition, ?int $seed = null): Edition
    {
        $performed = false;
        $drawnEdition = DB::transaction(function () use ($edition, $seed, &$performed): Edition {
            $performed = false;
            Group::query()->whereKey($edition->group_id)->lockForUpdate()->firstOrFail();
            $lockedEdition = Edition::query()->whereKey($edition->id)->lockForUpdate()->firstOrFail();
            /** @var Collection<int, EditionParticipant> $participants */
            $participants = $lockedEdition->participants()->orderBy('id')->lockForUpdate()->get();
            /** @var Collection<int, DrawConstraint> $constraints */
            $constraints = $lockedEdition->drawConstraints()->orderBy('id')->lockForUpdate()->get();
            /** @var Collection<int, Assignment> $existing */
            $existing = $lockedEdition->assignments()->orderBy('id')->lockForUpdate()->get();

            if ($existing->isNotEmpty()) {
                if ($this->isComplete($participants, $existing)
                    && in_array($lockedEdition->status, [EditionStatus::Drawn, EditionStatus::Revealed, EditionStatus::Archived], true)) {
                    $this->createConversations->handle($lockedEdition);

                    return $lockedEdition;
                }

                throw new DrawConflictException(DrawFailureCode::CorruptAssignments);
            }

            if ($lockedEdition->status !== EditionStatus::Open) {
                throw new DrawConflictException(DrawFailureCode::InvalidEditionState);
            }

            try {
                $input = $this->buildInput->handle(
                    $lockedEdition,
                    $participants,
                    $constraints,
                    $seed ?? random_int(0, PHP_INT_MAX),
                );
                $result = $this->registry->resolve($lockedEdition->type)->draw($input);
            } catch (DrawFailure $failure) {
                throw new DrawConflictException($failure->failureCode);
            }

            $timestamp = now();
            Assignment::query()->insert(array_map(
                fn ($pair): array => [
                    'edition_id' => $lockedEdition->id,
                    'giver_edition_participant_id' => $pair->giverParticipantId,
                    'receiver_edition_participant_id' => $pair->receiverParticipantId,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ],
                $result->pairs,
            ));
            $this->createConversations->handle($lockedEdition);
            $lockedEdition->update(['status' => EditionStatus::Drawn, 'drawn_at' => $timestamp]);
            $performed = true;

            return $lockedEdition->refresh();
        }, attempts: 3);

        if ($performed) {
            $this->notifyParticipants->handle($drawnEdition, new EditionDrawnNotification(
                groupId: $drawnEdition->group_id,
                editionId: $drawnEdition->id,
                editionName: $drawnEdition->name,
            ));
        }

        return $drawnEdition;
    }

    /**
     * @param  Collection<int, EditionParticipant>  $participants
     * @param  Collection<int, Assignment>  $assignments
     */
    private function isComplete(Collection $participants, Collection $assignments): bool
    {
        $participantIds = $participants->pluck('id')->sort()->values()->all();

        return $assignments->count() === $participants->count()
            && $assignments->pluck('giver_edition_participant_id')->sort()->values()->all() === $participantIds
            && $assignments->pluck('receiver_edition_participant_id')->sort()->values()->all() === $participantIds;
    }
}

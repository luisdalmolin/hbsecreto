<?php

namespace App\Actions\DrawConstraints;

use App\Draws\DrawConflictException;
use App\Draws\DrawFailureCode;
use App\Enums\DrawConstraintSource;
use App\Enums\DrawConstraintType;
use App\Enums\EditionStatus;
use App\Models\DrawConstraint;
use App\Models\Edition;
use App\Models\EditionParticipant;
use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class CopyDrawConstraintsFromPreviousEdition
{
    public function handle(Edition $edition, User $creator): CopiedDrawConstraints
    {
        return DB::transaction(function () use ($edition, $creator): CopiedDrawConstraints {
            Group::query()->whereKey($edition->group_id)->lockForUpdate()->firstOrFail();
            $lockedEdition = Edition::query()->whereKey($edition->id)->lockForUpdate()->firstOrFail();

            if (! in_array($lockedEdition->status, [EditionStatus::Draft, EditionStatus::Open], true)) {
                throw new DrawConflictException(DrawFailureCode::InvalidEditionState);
            }

            $sourceEdition = Edition::query()
                ->where('group_id', $lockedEdition->group_id)
                ->whereKeyNot($lockedEdition->id)
                ->where('id', '<', $lockedEdition->id)
                ->latest('id')
                ->first();

            if ($sourceEdition === null) {
                return new CopiedDrawConstraints(null, new Collection, 0, 0, 0);
            }

            $targetParticipants = $lockedEdition->participants()
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('group_member_id');
            $existingConstraints = $lockedEdition->drawConstraints()
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $sourceConstraints = $sourceEdition->drawConstraints()
                ->where('type', DrawConstraintType::MustNotPair)
                ->where('source', DrawConstraintSource::Admin)
                ->with(['giver:id,group_member_id', 'receiver:id,group_member_id'])
                ->orderBy('id')
                ->get();
            /** @var Collection<int, DrawConstraint> $copied */
            $copied = new Collection;
            $skippedMissingParticipants = 0;
            $skippedDuplicates = 0;
            $skippedConflicts = 0;

            foreach ($sourceConstraints as $sourceConstraint) {
                $giverMemberId = $sourceConstraint->giver?->group_member_id;
                $receiverMemberId = $sourceConstraint->receiver?->group_member_id;
                $giver = is_int($giverMemberId) ? $targetParticipants->get($giverMemberId) : null;
                $receiver = is_int($receiverMemberId) ? $targetParticipants->get($receiverMemberId) : null;

                if (! $giver instanceof EditionParticipant || ! $receiver instanceof EditionParticipant) {
                    $skippedMissingParticipants++;

                    continue;
                }

                if ($giver->id > $receiver->id) {
                    [$giver, $receiver] = [$receiver, $giver];
                }

                $duplicate = $existingConstraints->contains(fn (DrawConstraint $constraint): bool => $constraint->type === DrawConstraintType::MustNotPair
                    && $constraint->giver_edition_participant_id === $giver->id
                    && $constraint->receiver_edition_participant_id === $receiver->id);

                if ($duplicate) {
                    $skippedDuplicates++;

                    continue;
                }

                $conflict = $existingConstraints->contains(fn (DrawConstraint $constraint): bool => $constraint->type === DrawConstraintType::MustPair
                    && (($constraint->giver_edition_participant_id === $giver->id && $constraint->receiver_edition_participant_id === $receiver->id)
                        || ($constraint->giver_edition_participant_id === $receiver->id && $constraint->receiver_edition_participant_id === $giver->id)));

                if ($conflict) {
                    $skippedConflicts++;

                    continue;
                }

                $constraint = DrawConstraint::query()->create([
                    'edition_id' => $lockedEdition->id,
                    'type' => DrawConstraintType::MustNotPair,
                    'giver_edition_participant_id' => $giver->id,
                    'receiver_edition_participant_id' => $receiver->id,
                    'source' => DrawConstraintSource::Admin,
                    'created_by' => $creator->id,
                ]);
                $copied->push($constraint);
                $existingConstraints->push($constraint);
            }

            return new CopiedDrawConstraints(
                $sourceEdition->id,
                $copied,
                $skippedMissingParticipants,
                $skippedDuplicates,
                $skippedConflicts,
            );
        });
    }
}

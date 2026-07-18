<?php

namespace App\Actions\DrawConstraints;

use App\Draws\DrawConflictException;
use App\Draws\DrawFailureCode;
use App\Enums\DrawConstraintSource;
use App\Enums\DrawConstraintType;
use App\Enums\EditionStatus;
use App\Enums\OrderStatus;
use App\Models\DrawConstraint;
use App\Models\Edition;
use App\Models\EditionParticipant;
use App\Models\Group;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class CreateDrawConstraint
{
    public function handle(
        Edition $edition,
        User $creator,
        DrawConstraintType $type,
        EditionParticipant $giver,
        EditionParticipant $receiver,
    ): DrawConstraint {
        if (! in_array($edition->status, [EditionStatus::Draft, EditionStatus::Open], true)) {
            throw new DrawConflictException(DrawFailureCode::InvalidEditionState);
        }

        if ($giver->edition_id !== $edition->id || $receiver->edition_id !== $edition->id || $giver->is($receiver)) {
            throw new DrawConflictException(DrawFailureCode::InvalidConstraint);
        }

        if ($type === DrawConstraintType::MustNotPair && $giver->id > $receiver->id) {
            [$giver, $receiver] = [$receiver, $giver];
        }

        return DB::transaction(function () use ($edition, $creator, $type, $giver, $receiver): DrawConstraint {
            Group::query()->whereKey($edition->group_id)->lockForUpdate()->firstOrFail();
            $lockedEdition = Edition::query()->whereKey($edition->id)->lockForUpdate()->firstOrFail();

            if (! in_array($lockedEdition->status, [EditionStatus::Draft, EditionStatus::Open], true)) {
                throw new DrawConflictException(DrawFailureCode::InvalidEditionState);
            }

            $lockedEdition->participants()->orderBy('id')->lockForUpdate()->get();
            $constraints = $lockedEdition->drawConstraints()->with('order')->orderBy('id')->lockForUpdate()->get();

            foreach ($constraints as $constraint) {
                if ($constraint->source === DrawConstraintSource::Purchase
                    && $constraint->order instanceof Order
                    && in_array($constraint->order->status, [OrderStatus::Failed, OrderStatus::Refunded], true)) {
                    continue;
                }

                $sameDirection = $constraint->giver_edition_participant_id === $giver->id
                    && $constraint->receiver_edition_participant_id === $receiver->id;
                $reverseDirection = $constraint->giver_edition_participant_id === $receiver->id
                    && $constraint->receiver_edition_participant_id === $giver->id;
                $duplicate = $constraint->type === $type && $sameDirection;
                $forcedGiverConflict = $type === DrawConstraintType::MustPair
                    && $constraint->type === DrawConstraintType::MustPair
                    && $constraint->giver_edition_participant_id === $giver->id;
                $forcedReceiverConflict = $type === DrawConstraintType::MustPair
                    && $constraint->type === DrawConstraintType::MustPair
                    && $constraint->receiver_edition_participant_id === $receiver->id;
                $forcedExcluded = $constraint->type !== $type && ($sameDirection || $reverseDirection);

                if ($duplicate || $forcedGiverConflict || $forcedReceiverConflict || $forcedExcluded) {
                    throw new DrawConflictException(DrawFailureCode::ConflictingConstraints);
                }
            }

            $constraint = DrawConstraint::query()->create([
                'edition_id' => $edition->id,
                'type' => $type,
                'giver_edition_participant_id' => $giver->id,
                'receiver_edition_participant_id' => $receiver->id,
                'source' => DrawConstraintSource::Admin,
                'created_by' => $creator->id,
            ]);

            return $constraint;
        });
    }
}

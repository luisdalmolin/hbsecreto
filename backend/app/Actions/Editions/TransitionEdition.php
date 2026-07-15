<?php

namespace App\Actions\Editions;

use App\Actions\Notifications\NotifyEditionParticipants;
use App\Enums\EditionStatus;
use App\Exceptions\DomainConflictException;
use App\Models\Edition;
use App\Notifications\EditionRevealedNotification;
use Illuminate\Support\Facades\DB;

final class TransitionEdition
{
    public function __construct(private readonly NotifyEditionParticipants $notifyParticipants) {}

    public function handle(Edition $edition, EditionStatus $target): Edition
    {
        $transitionedEdition = DB::transaction(function () use ($edition, $target): Edition {
            $lockedEdition = Edition::query()->lockForUpdate()->findOrFail($edition->id);
            $expected = match ($target) {
                EditionStatus::Open => EditionStatus::Draft,
                EditionStatus::Revealed => EditionStatus::Drawn,
                EditionStatus::Archived => EditionStatus::Revealed,
                default => throw new DomainConflictException('editions.invalid_transition'),
            };

            if ($lockedEdition->status !== $expected) {
                throw new DomainConflictException('editions.invalid_transition');
            }

            if ($target === EditionStatus::Open && $lockedEdition->participants()->count() < 2) {
                throw new DomainConflictException('editions.minimum_participants');
            }

            $changes = ['status' => $target];

            if ($target === EditionStatus::Revealed) {
                $changes['revealed_at'] = now();
            }

            $lockedEdition->update($changes);

            return $lockedEdition->refresh();
        });

        if ($target === EditionStatus::Revealed) {
            $this->notifyParticipants->handle($transitionedEdition, new EditionRevealedNotification(
                groupId: $transitionedEdition->group_id,
                editionId: $transitionedEdition->id,
                editionName: $transitionedEdition->name,
            ));
        }

        return $transitionedEdition;
    }
}

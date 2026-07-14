<?php

namespace App\Actions\Wishes;

use App\Enums\EditionStatus;
use App\Exceptions\DomainConflictException;
use App\Models\Edition;
use App\Models\Wish;
use Illuminate\Support\Facades\DB;

final class DeleteWish
{
    public function handle(Edition $edition, Wish $wish): void
    {
        DB::transaction(function () use ($edition, $wish): void {
            $lockedEdition = Edition::query()->whereKey($edition->id)->lockForUpdate()->firstOrFail();

            if ($lockedEdition->status === EditionStatus::Archived) {
                throw new DomainConflictException('wishes.archived');
            }

            $lockedWish = Wish::query()
                ->whereKey($wish->id)
                ->whereHas('editionParticipant', fn ($participants) => $participants->whereBelongsTo($lockedEdition))
                ->lockForUpdate()
                ->firstOrFail();
            $participant = $lockedWish->editionParticipant()->lockForUpdate()->firstOrFail();
            $wishes = $participant->wishes()->lockForUpdate()->get();
            $lockedWish->delete();

            foreach ($wishes->reject->is($lockedWish)->values() as $sortOrder => $remainingWish) {
                if ($remainingWish->sort_order !== $sortOrder) {
                    $remainingWish->update(['sort_order' => $sortOrder]);
                }
            }
        }, attempts: 3);
    }
}

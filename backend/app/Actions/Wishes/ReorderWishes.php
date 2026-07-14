<?php

namespace App\Actions\Wishes;

use App\Enums\EditionStatus;
use App\Exceptions\DomainConflictException;
use App\Models\Edition;
use App\Models\EditionParticipant;
use App\Models\Wish;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class ReorderWishes
{
    /**
     * @param  list<int>  $wishIds
     * @return Collection<int, Wish>
     */
    public function handle(Edition $edition, EditionParticipant $participant, array $wishIds): Collection
    {
        return DB::transaction(function () use ($edition, $participant, $wishIds): Collection {
            $lockedEdition = Edition::query()->whereKey($edition->id)->lockForUpdate()->firstOrFail();

            if ($lockedEdition->status === EditionStatus::Archived) {
                throw new DomainConflictException('wishes.archived');
            }

            $lockedParticipant = $lockedEdition->participants()->whereKey($participant->id)->lockForUpdate()->firstOrFail();
            $wishes = $lockedParticipant->wishes()->lockForUpdate()->get();
            $storedIds = $wishes->pluck('id')->sort()->values()->all();
            $requestedIds = collect($wishIds)->sort()->values()->all();

            if ($storedIds !== $requestedIds) {
                throw new DomainConflictException('wishes.invalid_order');
            }

            $wishesById = $wishes->keyBy('id');
            $orderedWishes = new Collection;

            foreach ($wishIds as $sortOrder => $wishId) {
                $orderedWish = $wishesById->get($wishId);

                if (! $orderedWish instanceof Wish) {
                    throw new DomainConflictException('wishes.invalid_order');
                }

                if ($orderedWish->sort_order !== $sortOrder) {
                    $orderedWish->update(['sort_order' => $sortOrder]);
                }

                $orderedWishes->push($orderedWish);
            }

            return $orderedWishes;
        }, attempts: 3);
    }
}

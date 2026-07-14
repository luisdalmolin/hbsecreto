<?php

namespace App\Actions\Wishes;

use App\Enums\EditionStatus;
use App\Exceptions\DomainConflictException;
use App\Models\Edition;
use App\Models\EditionParticipant;
use App\Models\Wish;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CreateWish
{
    public function handle(Edition $edition, EditionParticipant $participant, string $description): Wish
    {
        return DB::transaction(function () use ($edition, $participant, $description): Wish {
            $lockedEdition = Edition::query()->whereKey($edition->id)->lockForUpdate()->firstOrFail();

            if ($lockedEdition->status === EditionStatus::Archived) {
                throw new DomainConflictException('wishes.archived');
            }

            $lockedParticipant = $lockedEdition->participants()->whereKey($participant->id)->lockForUpdate()->firstOrFail();
            $lastSortOrder = $lockedParticipant->wishes()->lockForUpdate()->get(['id', 'sort_order'])->max('sort_order');

            return $lockedParticipant->wishes()->create([
                'description' => Str::of($description)->trim()->toString(),
                'sort_order' => is_int($lastSortOrder) ? $lastSortOrder + 1 : 0,
            ]);
        }, attempts: 3);
    }
}

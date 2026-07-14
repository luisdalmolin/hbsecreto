<?php

namespace App\Actions\Wishes;

use App\Enums\EditionStatus;
use App\Exceptions\DomainConflictException;
use App\Models\Edition;
use App\Models\Wish;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class UpdateWish
{
    public function handle(Edition $edition, Wish $wish, string $description): Wish
    {
        return DB::transaction(function () use ($edition, $wish, $description): Wish {
            $lockedEdition = Edition::query()->whereKey($edition->id)->lockForUpdate()->firstOrFail();

            if ($lockedEdition->status === EditionStatus::Archived) {
                throw new DomainConflictException('wishes.archived');
            }

            $lockedWish = Wish::query()
                ->whereKey($wish->id)
                ->whereHas('editionParticipant', fn ($participants) => $participants->whereBelongsTo($lockedEdition))
                ->lockForUpdate()
                ->firstOrFail();
            $lockedWish->update(['description' => Str::of($description)->trim()->toString()]);

            return $lockedWish->refresh();
        }, attempts: 3);
    }
}

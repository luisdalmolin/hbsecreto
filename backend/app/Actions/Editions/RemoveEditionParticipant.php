<?php

namespace App\Actions\Editions;

use App\Enums\EditionStatus;
use App\Exceptions\DomainConflictException;
use App\Models\Edition;
use App\Models\EditionParticipant;
use Illuminate\Support\Facades\DB;

final class RemoveEditionParticipant
{
    public function handle(EditionParticipant $participant): void
    {
        DB::transaction(function () use ($participant): void {
            $edition = Edition::query()->lockForUpdate()->findOrFail($participant->edition_id);

            if (! in_array($edition->status, [EditionStatus::Draft, EditionStatus::Open], true)) {
                throw new DomainConflictException('editions.roster_frozen');
            }

            if ($edition->status === EditionStatus::Open && $edition->participants()->count() <= 2) {
                throw new DomainConflictException('editions.minimum_open_participants');
            }

            EditionParticipant::query()->whereKey($participant->id)->delete();
        });
    }
}

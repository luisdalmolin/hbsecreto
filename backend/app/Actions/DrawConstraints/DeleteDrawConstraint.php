<?php

namespace App\Actions\DrawConstraints;

use App\Draws\DrawConflictException;
use App\Draws\DrawFailureCode;
use App\Enums\EditionStatus;
use App\Models\DrawConstraint;
use App\Models\Edition;
use App\Models\Group;
use Illuminate\Support\Facades\DB;

final class DeleteDrawConstraint
{
    public function handle(DrawConstraint $constraint): void
    {
        DB::transaction(function () use ($constraint): void {
            $edition = $constraint->edition()->firstOrFail();
            Group::query()->whereKey($edition->group_id)->lockForUpdate()->firstOrFail();
            $lockedEdition = Edition::query()->whereKey($edition->id)->lockForUpdate()->firstOrFail();

            if (! in_array($lockedEdition->status, [EditionStatus::Draft, EditionStatus::Open], true)) {
                throw new DrawConflictException(DrawFailureCode::InvalidEditionState);
            }

            $lockedConstraint = $lockedEdition->drawConstraints()->whereKey($constraint->id)->lockForUpdate()->firstOrFail();
            $lockedConstraint->delete();
        });
    }
}

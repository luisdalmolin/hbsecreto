<?php

namespace App\Actions\Draws;

use App\Draws\DrawConflictException;
use App\Draws\DrawFailure;
use App\Draws\DrawFailureCode;
use App\Draws\DrawResult;
use App\Draws\EditionTypeRegistry;
use App\Enums\EditionStatus;
use App\Models\Edition;

final readonly class PreflightDraw
{
    public function __construct(
        private BuildDrawInput $buildInput,
        private EditionTypeRegistry $registry,
    ) {}

    public function handle(Edition $edition, ?int $seed = null): DrawResult
    {
        if (! in_array($edition->status, [EditionStatus::Draft, EditionStatus::Open], true)
            || $edition->assignments()->exists()) {
            throw new DrawConflictException(DrawFailureCode::InvalidEditionState);
        }

        try {
            $input = $this->buildInput->handle(
                $edition,
                $edition->participants()->orderBy('id')->get(),
                $edition->drawConstraints()->orderBy('id')->get(),
                $seed ?? random_int(0, PHP_INT_MAX),
            );

            return $this->registry->resolve($edition->type)->draw($input);
        } catch (DrawFailure $failure) {
            throw new DrawConflictException($failure->failureCode);
        }
    }
}

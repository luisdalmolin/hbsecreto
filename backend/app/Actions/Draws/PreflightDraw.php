<?php

namespace App\Actions\Draws;

use App\Actions\Orders\ReconcileExpiredPendingOrders;
use App\Draws\DrawConflictException;
use App\Draws\DrawFailure;
use App\Draws\DrawFailureCode;
use App\Draws\DrawResult;
use App\Draws\EditionTypeRegistry;
use App\Enums\EditionStatus;
use App\Enums\OrderStatus;
use App\Models\Edition;

final readonly class PreflightDraw
{
    public function __construct(
        private BuildDrawInput $buildInput,
        private EditionTypeRegistry $registry,
        private ReconcileExpiredPendingOrders $reconcileExpired,
    ) {}

    public function handle(Edition $edition, ?int $seed = null): DrawResult
    {
        if (! in_array($edition->status, [EditionStatus::Draft, EditionStatus::Open], true)
            || $edition->assignments()->exists()) {
            throw new DrawConflictException(DrawFailureCode::InvalidEditionState);
        }

        $this->reconcileExpired->handle($edition);

        if ($edition->orders()->where('status', OrderStatus::Pending)->exists()) {
            throw new DrawConflictException(DrawFailureCode::PendingPayment);
        }

        try {
            $input = $this->buildInput->handle(
                $edition,
                $edition->participants()->orderBy('id')->get(),
                $edition->drawConstraints()->activeForDraw()->orderBy('id')->get(),
                $seed ?? random_int(0, PHP_INT_MAX),
            );

            return $this->registry->resolve($edition->type)->draw($input);
        } catch (DrawFailure $failure) {
            throw new DrawConflictException($failure->failureCode);
        }
    }
}

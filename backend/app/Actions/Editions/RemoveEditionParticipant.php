<?php

namespace App\Actions\Editions;

use App\Enums\DrawConstraintSource;
use App\Enums\EditionStatus;
use App\Enums\OrderStatus;
use App\Exceptions\DomainConflictException;
use App\Models\DrawConstraint;
use App\Models\Edition;
use App\Models\EditionParticipant;
use App\Models\Group;
use App\Models\Message;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

final class RemoveEditionParticipant
{
    public function handle(EditionParticipant $participant): void
    {
        DB::transaction(function () use ($participant): void {
            Group::query()->whereKey($participant->group_id)->lockForUpdate()->firstOrFail();
            $edition = Edition::query()->lockForUpdate()->findOrFail($participant->edition_id);
            $lockedParticipant = EditionParticipant::query()
                ->whereKey($participant->id)
                ->whereBelongsTo($edition)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($edition->status, [EditionStatus::Draft, EditionStatus::Open], true)) {
                throw new DomainConflictException('editions.roster_frozen');
            }

            if ($edition->status === EditionStatus::Open && $edition->participants()->count() <= 2) {
                throw new DomainConflictException('editions.minimum_open_participants');
            }

            $purchaseOrderIds = DrawConstraint::query()
                ->whereBelongsTo($edition)
                ->where('source', DrawConstraintSource::Purchase)
                ->where(function ($constraints) use ($lockedParticipant): void {
                    $constraints
                        ->where('giver_edition_participant_id', $lockedParticipant->id)
                        ->orWhere('receiver_edition_participant_id', $lockedParticipant->id);
                })
                ->orderBy('id')
                ->lockForUpdate()
                ->pluck('order_id')
                ->filter(fn (mixed $orderId): bool => is_int($orderId));

            if ($purchaseOrderIds->isNotEmpty() && Order::query()
                ->whereKey($purchaseOrderIds)
                ->whereIn('status', [OrderStatus::Pending, OrderStatus::Paid])
                ->orderBy('id')
                ->lockForUpdate()
                ->first(['id']) !== null) {
                throw new DomainConflictException('editions.participant_has_active_purchase');
            }

            if (Message::query()
                ->where('edition_id', $edition->id)
                ->where('sender_edition_participant_id', $lockedParticipant->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->first(['id']) !== null) {
                throw new DomainConflictException('editions.participant_has_messages');
            }

            $lockedParticipant->delete();
        }, attempts: 3);
    }
}

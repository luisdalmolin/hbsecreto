<?php

namespace App\Actions\Orders;

use App\Draws\DrawConflictException;
use App\Draws\DrawFailureCode;
use App\Enums\DrawConstraintSource;
use App\Enums\DrawConstraintType;
use App\Enums\EditionStatus;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Exceptions\DomainConflictException;
use App\Models\DrawConstraint;
use App\Models\Edition;
use App\Models\EditionParticipant;
use App\Models\Group;
use App\Models\Order;
use App\Models\User;
use App\Services\Payments\PaymentGateway;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

final class CreatePickPurchase
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly ReconcileExpiredPendingOrders $reconcileExpired,
    ) {}

    public function handle(Edition $edition, User $buyer, EditionParticipant $receiver): Order
    {
        $requiresCheckout = false;
        $order = DB::transaction(function () use ($edition, $buyer, $receiver, &$requiresCheckout): Order {
            $requiresCheckout = false;
            Group::query()->whereKey($edition->group_id)->lockForUpdate()->firstOrFail();
            $lockedEdition = Edition::query()->whereKey($edition->id)->lockForUpdate()->firstOrFail();

            if (! in_array($lockedEdition->status, [EditionStatus::Draft, EditionStatus::Open], true)) {
                throw new DomainConflictException('orders.edition_locked');
            }

            $this->reconcileExpired->handle($lockedEdition);

            /** @var Collection<int, EditionParticipant> $participants */
            $participants = $lockedEdition->participants()
                ->with('groupMember')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $giver = $participants->first(fn (EditionParticipant $participant): bool => $participant->groupMember?->user_id === $buyer->id);
            $lockedReceiver = $participants->firstWhere('id', $receiver->id);

            if (! $giver instanceof EditionParticipant
                || ! $lockedReceiver instanceof EditionParticipant
                || $giver->is($lockedReceiver)) {
                throw new DomainConflictException('orders.invalid_participants');
            }

            /** @var Collection<int, DrawConstraint> $constraints */
            $constraints = $lockedEdition->drawConstraints()->with('order')->orderBy('id')->lockForUpdate()->get();
            $existingPurchase = $constraints->first(fn (DrawConstraint $constraint): bool => $constraint->source === DrawConstraintSource::Purchase
                && $constraint->giver_edition_participant_id === $giver->id);

            if ($existingPurchase instanceof DrawConstraint) {
                if ($existingPurchase->receiver_edition_participant_id !== $lockedReceiver->id
                    || ! $existingPurchase->order instanceof Order
                    || $existingPurchase->order->user_id !== $buyer->id
                    || $existingPurchase->order->status === OrderStatus::Refunded) {
                    throw new DomainConflictException('orders.conflicting_purchase');
                }

                $this->ensureNoConflict($constraints->reject->is($existingPurchase), $giver, $lockedReceiver);

                if ($existingPurchase->order->status === OrderStatus::Failed) {
                    $this->prepareCheckout($existingPurchase->order);
                    $requiresCheckout = true;
                }

                return $existingPurchase->order;
            }

            $this->ensureNoConflict($constraints, $giver, $lockedReceiver);

            $order = Order::query()->create([
                'user_id' => $buyer->id,
                'edition_id' => $lockedEdition->id,
                'type' => OrderType::PickPurchase,
                'amount_cents' => Config::integer('services.payments.pick_purchase_amount_cents'),
                'currency' => 'BRL',
                'status' => OrderStatus::Pending,
                'payment_provider' => $this->gateway->provider(),
                'metadata' => [],
            ]);
            $this->prepareCheckout($order);
            $requiresCheckout = true;
            $lockedEdition->drawConstraints()->create([
                'type' => DrawConstraintType::MustPair,
                'giver_edition_participant_id' => $giver->id,
                'receiver_edition_participant_id' => $lockedReceiver->id,
                'source' => DrawConstraintSource::Purchase,
                'order_id' => $order->id,
                'created_by' => null,
            ]);

            return $order;
        }, attempts: 3);

        if (! $requiresCheckout) {
            return $order;
        }

        try {
            $checkout = $this->gateway->createCheckout($order, $buyer->email);
            $metadata = $order->metadata;
            $metadata['checkoutUrl'] = $checkout->checkoutUrl;
            $metadata['checkout'] = $checkout->providerPayload;
            $order->update([
                'status' => OrderStatus::Pending,
                'provider_reference' => $checkout->providerReference,
                'metadata' => $metadata,
            ]);
        } catch (Throwable $exception) {
            $order->update([
                'status' => OrderStatus::Failed,
                'checkout_expires_at' => null,
            ]);

            throw $exception;
        }

        return $order->refresh();
    }

    private function prepareCheckout(Order $order): void
    {
        $metadata = $order->metadata;
        unset($metadata['checkoutUrl'], $metadata['checkout']);

        $order->update([
            'status' => OrderStatus::Pending,
            'provider_reference' => null,
            'checkout_idempotency_key' => (string) Str::uuid(),
            'checkout_expires_at' => now()->addMinutes(Config::integer('services.mercado_pago.checkout_expiry_minutes')),
            'metadata' => $metadata,
        ]);
    }

    /** @param Collection<int, DrawConstraint> $constraints */
    private function ensureNoConflict(Collection $constraints, EditionParticipant $giver, EditionParticipant $receiver): void
    {
        foreach ($constraints as $constraint) {
            if ($constraint->source === DrawConstraintSource::Purchase
                && $constraint->order instanceof Order
                && in_array($constraint->order->status, [OrderStatus::Failed, OrderStatus::Refunded], true)) {
                continue;
            }

            $sameDirection = $constraint->giver_edition_participant_id === $giver->id
                && $constraint->receiver_edition_participant_id === $receiver->id;
            $reverseDirection = $constraint->giver_edition_participant_id === $receiver->id
                && $constraint->receiver_edition_participant_id === $giver->id;
            $forcedGiverConflict = $constraint->type === DrawConstraintType::MustPair
                && $constraint->giver_edition_participant_id === $giver->id;
            $forcedReceiverConflict = $constraint->type === DrawConstraintType::MustPair
                && $constraint->receiver_edition_participant_id === $receiver->id;
            $forcedExcluded = $constraint->type === DrawConstraintType::MustNotPair && ($sameDirection || $reverseDirection);

            if ($forcedGiverConflict || $forcedReceiverConflict || $forcedExcluded) {
                throw new DrawConflictException(DrawFailureCode::ConflictingConstraints);
            }
        }
    }
}

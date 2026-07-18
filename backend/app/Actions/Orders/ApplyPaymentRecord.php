<?php

namespace App\Actions\Orders;

use App\Enums\EditionStatus;
use App\Enums\OrderStatus;
use App\Exceptions\DomainConflictException;
use App\Models\Edition;
use App\Models\Group;
use App\Models\Order;
use App\Services\Payments\CheckoutAttemptReference;
use App\Services\Payments\PaymentGateway;
use App\Services\Payments\PaymentRecordData;
use Illuminate\Support\Facades\DB;
use UnexpectedValueException;

final class ApplyPaymentRecord
{
    public function __construct(private readonly PaymentGateway $gateway) {}

    public function handle(PaymentRecordData $payment, string $paymentProvider): Order
    {
        $attemptReference = CheckoutAttemptReference::parse($payment->externalReference);

        if ($attemptReference === null) {
            throw new DomainConflictException('orders.payment_mismatch');
        }

        $order = Order::query()->findOrFail($attemptReference->orderId);

        if ($order->payment_provider !== $paymentProvider
            || $payment->amountCents !== $order->amount_cents
            || $payment->currency !== $order->currency) {
            throw new DomainConflictException('orders.payment_mismatch');
        }

        $requiresCompensation = false;
        $applied = DB::transaction(function () use ($order, $payment, $paymentProvider, $attemptReference, &$requiresCompensation): Order {
            $requiresCompensation = false;
            $edition = Edition::query()->whereKey($order->edition_id)->firstOrFail();
            Group::query()->whereKey($edition->group_id)->lockForUpdate()->firstOrFail();
            $lockedEdition = Edition::query()->whereKey($edition->id)->lockForUpdate()->firstOrFail();
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->payment_provider !== $paymentProvider
                || $payment->amountCents !== $lockedOrder->amount_cents
                || $payment->currency !== $lockedOrder->currency) {
                throw new DomainConflictException('orders.payment_mismatch');
            }

            $status = $this->status($payment->status);

            if ($attemptReference->attemptId !== $lockedOrder->checkout_idempotency_key) {
                $requiresCompensation = $status === OrderStatus::Paid;

                return $lockedOrder;
            }

            if ($status === OrderStatus::Paid
                && ! in_array($lockedOrder->status, [OrderStatus::Paid, OrderStatus::Refunded], true)
                && in_array($lockedEdition->status, [EditionStatus::Drawn, EditionStatus::Revealed, EditionStatus::Archived], true)) {
                $requiresCompensation = true;

                return $lockedOrder;
            }

            $metadata = $lockedOrder->metadata;
            $metadata['payment'] = $payment->providerPayload;
            $metadata['providerStatus'] = $payment->status;
            $metadata['providerStatusDetail'] = $payment->statusDetail;
            $updates = [
                'provider_reference' => $payment->providerReference,
                'metadata' => $metadata,
            ];

            if ($this->rank($status) > $this->rank($lockedOrder->status)) {
                $updates['status'] = $status;
            }

            if ($status === OrderStatus::Paid && $lockedOrder->paid_at === null) {
                $updates['paid_at'] = $payment->paidAt ?? now();
            }

            $lockedOrder->update($updates);

            return $lockedOrder->refresh();
        }, attempts: 3);

        if (! $requiresCompensation) {
            return $applied;
        }

        $refund = $this->gateway->refund(
            $payment->providerReference,
            'compensate-order-'.$attemptReference->orderId.'-attempt-'.$attemptReference->attemptId,
        );

        if ($this->status($refund->status) !== OrderStatus::Refunded) {
            throw new UnexpectedValueException('The provider did not confirm the compensating refund.');
        }

        return $this->handle($refund, $paymentProvider);
    }

    private function status(string $providerStatus): OrderStatus
    {
        return match ($providerStatus) {
            'approved' => OrderStatus::Paid,
            'rejected', 'cancelled', 'canceled', 'expired' => OrderStatus::Failed,
            'refunded', 'charged_back' => OrderStatus::Refunded,
            default => OrderStatus::Pending,
        };
    }

    private function rank(OrderStatus $status): int
    {
        return match ($status) {
            OrderStatus::Pending => 0,
            OrderStatus::Failed => 1,
            OrderStatus::Paid => 2,
            OrderStatus::Refunded => 3,
        };
    }
}

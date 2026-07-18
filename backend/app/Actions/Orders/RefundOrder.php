<?php

namespace App\Actions\Orders;

use App\Enums\EditionStatus;
use App\Enums\OrderStatus;
use App\Exceptions\DomainConflictException;
use App\Models\Edition;
use App\Models\Group;
use App\Models\Order;
use App\Services\Payments\PaymentGateway;
use Illuminate\Support\Facades\DB;

final readonly class RefundOrder
{
    public function __construct(
        private PaymentGateway $gateway,
        private ApplyPaymentRecord $applyPayment,
    ) {}

    public function handle(Order $order): Order
    {
        return DB::transaction(function () use ($order): Order {
            $edition = Edition::query()->whereKey($order->edition_id)->firstOrFail();
            Group::query()->whereKey($edition->group_id)->lockForUpdate()->firstOrFail();
            $lockedEdition = Edition::query()->whereKey($edition->id)->lockForUpdate()->firstOrFail();
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (! in_array($lockedEdition->status, [EditionStatus::Draft, EditionStatus::Open], true)
                || $lockedOrder->status !== OrderStatus::Paid
                || $lockedOrder->provider_reference === null) {
                throw new DomainConflictException('orders.refund_unavailable');
            }

            $payment = $this->gateway->refund(
                $lockedOrder->provider_reference,
                'pick-order-refund-'.$lockedOrder->id,
            );

            return $this->applyPayment->handle($payment, $this->gateway->provider());
        }, attempts: 3);
    }
}

<?php

namespace App\Data\Api\V1\Orders;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\Order;
use OpenApi\Attributes as OA;
use Spatie\LaravelData\Resource;

#[OA\Schema(
    schema: 'Order',
    required: ['id', 'editionId', 'receiverParticipantId', 'type', 'amountCents', 'currency', 'status', 'paymentProvider', 'checkoutUrl', 'paidAt', 'createdAt'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', minimum: 1),
        new OA\Property(property: 'editionId', type: 'integer', minimum: 1),
        new OA\Property(property: 'receiverParticipantId', oneOf: [new OA\Schema(type: 'integer', minimum: 1), new OA\Schema(type: 'null')]),
        new OA\Property(property: 'checkoutUrl', oneOf: [new OA\Schema(type: 'string', format: 'uri'), new OA\Schema(type: 'null')]),
        new OA\Property(property: 'paidAt', oneOf: [new OA\Schema(type: 'string', format: 'date-time'), new OA\Schema(type: 'null')]),
    ],
)]
final class OrderData extends Resource
{
    public function __construct(
        public int $id,
        public int $editionId,
        public ?int $receiverParticipantId,
        #[OA\Property(type: 'string', enum: ['pick_purchase'])] public OrderType $type,
        #[OA\Property(minimum: 1, example: 499)] public int $amountCents,
        #[OA\Property(example: 'BRL')] public string $currency,
        #[OA\Property(type: 'string', enum: ['pending', 'paid', 'failed', 'refunded'])] public OrderStatus $status,
        #[OA\Property(example: 'mercadopago')] public string $paymentProvider,
        public ?string $checkoutUrl,
        public ?string $paidAt,
        #[OA\Property(type: 'string', format: 'date-time')] public string $createdAt,
    ) {}

    public static function fromOrder(Order $order): self
    {
        if ($order->created_at === null) {
            throw new \LogicException('A persisted order must have a creation timestamp.');
        }

        $checkoutUrl = $order->metadata['checkoutUrl'] ?? null;

        return new self(
            id: $order->id,
            editionId: $order->edition_id,
            receiverParticipantId: $order->drawConstraint?->receiver_edition_participant_id,
            type: $order->type,
            amountCents: $order->amount_cents,
            currency: $order->currency,
            status: $order->status,
            paymentProvider: $order->payment_provider,
            checkoutUrl: is_string($checkoutUrl) ? $checkoutUrl : null,
            paidAt: $order->paid_at?->toIso8601String(),
            createdAt: $order->created_at->toIso8601String(),
        );
    }
}

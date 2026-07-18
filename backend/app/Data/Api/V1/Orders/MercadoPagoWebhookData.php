<?php

namespace App\Data\Api\V1\Orders;

use OpenApi\Attributes as OA;
use Spatie\LaravelData\Data;

#[OA\Schema(schema: 'MercadoPagoWebhook', required: ['id', 'type', 'action', 'data'])]
final class MercadoPagoWebhookData extends Data
{
    public function __construct(
        #[OA\Property(type: 'integer')]
        public int $id,
        #[OA\Property(example: 'payment')] public string $type,
        #[OA\Property(example: 'payment.updated')] public string $action,
        #[OA\Property(ref: '#/components/schemas/MercadoPagoWebhookResource')]
        public MercadoPagoWebhookResourceData $data,
    ) {}
}

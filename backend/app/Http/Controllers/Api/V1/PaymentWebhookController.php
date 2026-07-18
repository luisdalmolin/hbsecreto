<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Orders\ProcessPaymentWebhook;
use App\Data\Api\V1\Orders\MercadoPagoWebhookData;
use App\Exceptions\InvalidPaymentWebhook;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Payment webhooks')]
final class PaymentWebhookController extends Controller
{
    #[OA\Post(
        path: '/api/v1/payments/mercadopago/webhook', operationId: 'processMercadoPagoWebhook', tags: ['Payment webhooks'],
        parameters: [
            new OA\QueryParameter(name: 'data_id', description: 'PHP-normalized form of Mercado Pago\'s data.id query parameter.', required: true, schema: new OA\Schema(type: 'string')),
            new OA\HeaderParameter(name: 'x-signature', required: true, schema: new OA\Schema(type: 'string')),
            new OA\HeaderParameter(name: 'x-request-id', required: false, schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/MercadoPagoWebhook')),
        responses: [
            new OA\Response(response: 200, description: 'Webhook accepted.'),
            new OA\Response(response: 401, description: 'The webhook signature is invalid.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 409, description: 'The authoritative payment does not match its order.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 422, description: 'The webhook payload is invalid.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function __invoke(MercadoPagoWebhookData $data, Request $request, ProcessPaymentWebhook $process): Response
    {
        $queryResourceId = $request->query('data_id', $request->query('data.id'));
        $bodyResourceId = (string) $data->data->id;

        if (! is_string($queryResourceId) || $queryResourceId === '' || $queryResourceId !== $bodyResourceId || $data->type !== 'payment') {
            throw new InvalidPaymentWebhook('The payment webhook resource does not match.');
        }

        $process->handle(
            signature: $request->header('x-signature', ''),
            requestId: $request->header('x-request-id'),
            providerEventId: (string) $data->id,
            resourceId: $queryResourceId,
            payload: [
                'id' => $data->id,
                'type' => $data->type,
                'action' => $data->action,
                'data' => ['id' => $data->data->id],
            ],
        );

        return response('', 200);
    }
}

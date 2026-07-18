<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Orders\CreatePickPurchase;
use App\Actions\Orders\RefundOrder;
use App\Data\Api\V1\Orders\CreatePickOrderData;
use App\Data\Api\V1\Orders\OrderCollectionData;
use App\Data\Api\V1\Orders\OrderData;
use App\Data\Api\V1\Shared\PaginationMetaData;
use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\Group;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use OpenApi\Attributes as OA;
use Spatie\LaravelData\DataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

#[OA\Tag(name: 'Orders')]
final class OrderController extends Controller
{
    #[Authorize('viewAny', Order::class)]
    #[OA\Get(
        path: '/api/v1/orders', operationId: 'listOrders', tags: ['Orders'], security: [['bearerAuth' => []]],
        parameters: [
            new OA\QueryParameter(name: 'filter[edition_id]', schema: new OA\Schema(type: 'integer', minimum: 1)),
            new OA\QueryParameter(name: 'page', schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Orders owned by the authenticated user.', content: new OA\JsonContent(ref: '#/components/schemas/OrderCollection')),
            new OA\Response(response: 401, description: 'Authentication is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function index(Request $request): OrderCollectionData
    {
        /** @var User $user */
        $user = $request->user();
        $orders = QueryBuilder::for($user->orders()->with('drawConstraint')->getQuery())
            ->allowedFilters('status', AllowedFilter::exact('edition_id'))
            ->allowedIncludes()
            ->allowedSorts('created_at', 'paid_at')
            ->allowedFields('orders.id', 'orders.edition_id', 'orders.type', 'orders.amount_cents', 'orders.currency', 'orders.status', 'orders.payment_provider', 'orders.paid_at', 'orders.created_at')
            ->defaultSort('-created_at')
            ->paginate();
        $items = collect($orders->items())->map(fn (Order $order): OrderData => OrderData::fromOrder($order));
        /** @var DataCollection<int, OrderData> $data */
        $data = OrderData::collect($items, DataCollection::class);

        return new OrderCollectionData(
            data: $data,
            meta: new PaginationMetaData($orders->currentPage(), $orders->lastPage(), $orders->perPage(), $orders->total()),
        );
    }

    #[Authorize('create', [Order::class, 'edition'])]
    #[OA\Post(
        path: '/api/v1/groups/{group}/editions/{edition}/pick-orders', operationId: 'createPickOrder', tags: ['Orders'], security: [['bearerAuth' => []]],
        parameters: [new OA\PathParameter(name: 'group', required: true, schema: new OA\Schema(type: 'integer')), new OA\PathParameter(name: 'edition', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/CreatePickOrderRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Pick purchase checkout created.', content: new OA\JsonContent(ref: '#/components/schemas/Order')),
            new OA\Response(response: 401, description: 'Authentication is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 403, description: 'An active edition participant is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'The group, edition, or receiver is not visible.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 409, description: 'The purchase conflicts with the edition state or draw rules.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 422, description: 'The request payload is invalid.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function store(CreatePickOrderData $data, Group $group, Edition $edition, Request $request, CreatePickPurchase $create): OrderData
    {
        /** @var User $user */
        $user = $request->user();
        $receiver = $edition->participants()->findOrFail($data->receiverParticipantId);

        return OrderData::fromOrder($create->handle($edition, $user, $receiver)->load('drawConstraint'));
    }

    #[Authorize('view', 'order')]
    #[OA\Get(
        path: '/api/v1/orders/{order}', operationId: 'getOrder', tags: ['Orders'], security: [['bearerAuth' => []]],
        parameters: [new OA\PathParameter(name: 'order', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Owned order details.', content: new OA\JsonContent(ref: '#/components/schemas/Order')),
            new OA\Response(response: 401, description: 'Authentication is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 403, description: 'Only the buyer may view this order.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function show(Order $order): OrderData
    {
        return OrderData::fromOrder($order->load('drawConstraint'));
    }

    #[Authorize('refund', 'order')]
    #[OA\Post(
        path: '/api/v1/orders/{order}/refund', operationId: 'refundOrder', tags: ['Orders'], security: [['bearerAuth' => []]],
        parameters: [new OA\PathParameter(name: 'order', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 201, description: 'Order refund completed.', content: new OA\JsonContent(ref: '#/components/schemas/Order')),
            new OA\Response(response: 401, description: 'Authentication is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 403, description: 'Only the buyer may refund this order.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 409, description: 'Only paid pre-draw orders may be refunded.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function refund(Order $order, RefundOrder $refund): OrderData
    {
        return OrderData::fromOrder($refund->handle($order)->load('drawConstraint'));
    }
}

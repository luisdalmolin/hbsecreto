<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Products\SearchAffiliateProducts;
use App\Data\Api\V1\Products\ProductCollectionData;
use App\Data\Api\V1\Products\ProductData;
use App\Data\Api\V1\Products\SearchProductsData;
use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\Group;
use App\Models\Wish;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use OpenApi\Attributes as OA;
use Spatie\LaravelData\DataCollection;

#[OA\Tag(name: 'Products')]
final class ProductController extends Controller
{
    #[Authorize('create', [Wish::class, 'edition'])]
    #[OA\Get(
        path: '/api/v1/groups/{group}/editions/{edition}/products/search', operationId: 'searchProducts', tags: ['Products'], security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'group', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'edition', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\QueryParameter(name: 'q', required: true, schema: new OA\Schema(type: 'string', minLength: 2, maxLength: 100)),
            new OA\QueryParameter(name: 'limit', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 20, default: 20)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Cached affiliate product search results.', content: new OA\JsonContent(ref: '#/components/schemas/ProductCollection')),
            new OA\Response(response: 401, description: 'Authentication is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 403, description: 'An active edition participant is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'The group or edition is not visible.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 422, description: 'The search query is invalid.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 502, description: 'The affiliate product catalog is unavailable.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function search(
        SearchProductsData $data,
        Group $group,
        Edition $edition,
        SearchAffiliateProducts $searchProducts,
    ): ProductCollectionData {
        $products = $searchProducts->handle($data->q, $data->limit);
        /** @var DataCollection<int, ProductData> $collection */
        $collection = ProductData::collect($products, DataCollection::class);

        return new ProductCollectionData($collection);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Wishes\CreateWish;
use App\Actions\Wishes\DeleteWish;
use App\Actions\Wishes\ReorderWishes;
use App\Actions\Wishes\UpdateWish;
use App\Data\Api\V1\Wishes\CreateWishData;
use App\Data\Api\V1\Wishes\ReorderWishesData;
use App\Data\Api\V1\Wishes\UpdateWishData;
use App\Data\Api\V1\Wishes\WishCollectionData;
use App\Data\Api\V1\Wishes\WishData;
use App\Enums\GroupMemberStatus;
use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\EditionParticipant;
use App\Models\Group;
use App\Models\Product;
use App\Models\User;
use App\Models\Wish;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use OpenApi\Attributes as OA;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Optional;
use Spatie\QueryBuilder\QueryBuilder;

#[OA\Tag(name: 'Wishes')]
final class WishController extends Controller
{
    #[Authorize('viewOwn', [Wish::class, 'edition'])]
    #[OA\Get(
        path: '/api/v1/groups/{group}/editions/{edition}/my-wishes', operationId: 'getMyWishes', tags: ['Wishes'], security: [['bearerAuth' => []]],
        parameters: [new OA\PathParameter(name: 'group', required: true, schema: new OA\Schema(type: 'integer')), new OA\PathParameter(name: 'edition', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'The authenticated participant wishes.', content: new OA\JsonContent(ref: '#/components/schemas/WishCollection')),
            new OA\Response(response: 401, description: 'Authentication is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 403, description: 'An edition participant is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'The group or edition is not visible.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function index(Group $group, Edition $edition, Request $request): WishCollectionData
    {
        $participant = $this->participantFor($edition, $request);
        $baseQuery = Wish::query()->whereBelongsTo($participant, 'editionParticipant')->with('product');
        $wishes = QueryBuilder::for($baseQuery)
            ->allowedFilters()
            ->allowedIncludes()
            ->allowedSorts()
            ->allowedFields('wishes.id', 'wishes.description', 'wishes.sort_order')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        /** @var DataCollection<int, WishData> $data */
        $data = WishData::collect($wishes, DataCollection::class);

        return new WishCollectionData($data);
    }

    #[Authorize('create', [Wish::class, 'edition'])]
    #[OA\Post(
        path: '/api/v1/groups/{group}/editions/{edition}/my-wishes', operationId: 'createWish', tags: ['Wishes'], security: [['bearerAuth' => []]],
        parameters: [new OA\PathParameter(name: 'group', required: true, schema: new OA\Schema(type: 'integer')), new OA\PathParameter(name: 'edition', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/CreateWishRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Wish created.', content: new OA\JsonContent(ref: '#/components/schemas/Wish')),
            new OA\Response(response: 401, description: 'Authentication is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 403, description: 'An edition participant is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'The group or edition is not visible.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 409, description: 'The edition is archived.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 422, description: 'The request payload is invalid.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function store(CreateWishData $data, Group $group, Edition $edition, Request $request, CreateWish $createWish): WishData
    {
        $participant = $this->participantFor($edition, $request);

        $product = $data->productId === null ? null : Product::query()->findOrFail($data->productId);

        return WishData::from($createWish->handle($edition, $participant, $data->description, $product));
    }

    #[Authorize('update', 'wish')]
    #[OA\Patch(
        path: '/api/v1/groups/{group}/editions/{edition}/my-wishes/{wish}', operationId: 'updateWish', tags: ['Wishes'], security: [['bearerAuth' => []]],
        parameters: [new OA\PathParameter(name: 'group', required: true, schema: new OA\Schema(type: 'integer')), new OA\PathParameter(name: 'edition', required: true, schema: new OA\Schema(type: 'integer')), new OA\PathParameter(name: 'wish', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UpdateWishRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Wish updated.', content: new OA\JsonContent(ref: '#/components/schemas/Wish')),
            new OA\Response(response: 401, description: 'Authentication is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 403, description: 'Only the wish owner may update it.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'The group, edition, or wish is not visible.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 409, description: 'The edition is archived.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 422, description: 'The request payload is invalid.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function update(UpdateWishData $data, Group $group, Edition $edition, Wish $wish, UpdateWish $updateWish): WishData
    {
        $replaceProduct = ! $data->productId instanceof Optional;
        $product = is_int($data->productId) ? Product::query()->findOrFail($data->productId) : null;

        return WishData::from($updateWish->handle($edition, $wish, $data->description, $replaceProduct, $product));
    }

    #[Authorize('delete', 'wish')]
    #[OA\Delete(
        path: '/api/v1/groups/{group}/editions/{edition}/my-wishes/{wish}', operationId: 'deleteWish', tags: ['Wishes'], security: [['bearerAuth' => []]],
        parameters: [new OA\PathParameter(name: 'group', required: true, schema: new OA\Schema(type: 'integer')), new OA\PathParameter(name: 'edition', required: true, schema: new OA\Schema(type: 'integer')), new OA\PathParameter(name: 'wish', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 204, description: 'Wish deleted.'),
            new OA\Response(response: 401, description: 'Authentication is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 403, description: 'Only the wish owner may delete it.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'The group, edition, or wish is not visible.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 409, description: 'The edition is archived.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function destroy(Group $group, Edition $edition, Wish $wish, DeleteWish $deleteWish): Response
    {
        $deleteWish->handle($edition, $wish);

        return response()->noContent();
    }

    #[Authorize('reorder', [Wish::class, 'edition'])]
    #[OA\Put(
        path: '/api/v1/groups/{group}/editions/{edition}/my-wishes/order', operationId: 'reorderWishes', tags: ['Wishes'], security: [['bearerAuth' => []]],
        parameters: [new OA\PathParameter(name: 'group', required: true, schema: new OA\Schema(type: 'integer')), new OA\PathParameter(name: 'edition', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ReorderWishesRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Wishes reordered.', content: new OA\JsonContent(ref: '#/components/schemas/WishCollection')),
            new OA\Response(response: 401, description: 'Authentication is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 403, description: 'An edition participant is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'The group or edition is not visible.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 409, description: 'The list is incomplete or the edition is archived.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 422, description: 'The request payload is invalid.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function reorder(ReorderWishesData $data, Group $group, Edition $edition, Request $request, ReorderWishes $reorderWishes): WishCollectionData
    {
        $participant = $this->participantFor($edition, $request);
        $wishes = $reorderWishes->handle($edition, $participant, $data->wishIds);
        /** @var DataCollection<int, WishData> $collection */
        $collection = WishData::collect($wishes, DataCollection::class);

        return new WishCollectionData($collection);
    }

    private function participantFor(Edition $edition, Request $request): EditionParticipant
    {
        /** @var User $user */
        $user = $request->user();

        return $edition->participants()
            ->whereHas('groupMember', fn ($members) => $members
                ->whereBelongsTo($user)
                ->where('status', GroupMemberStatus::Active))
            ->firstOrFail();
    }
}

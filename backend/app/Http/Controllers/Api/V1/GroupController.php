<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Groups\CreateGroup;
use App\Data\Api\V1\Groups\CreateGroupData;
use App\Data\Api\V1\Groups\GroupCollectionData;
use App\Data\Api\V1\Groups\GroupData;
use App\Data\Api\V1\Groups\UpdateGroupData;
use App\Data\Api\V1\Shared\PaginationMetaData;
use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use OpenApi\Attributes as OA;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Optional;
use Spatie\QueryBuilder\QueryBuilder;

#[OA\Tag(name: 'Groups')]
final class GroupController extends Controller
{
    #[Authorize('viewAny', Group::class)]
    #[OA\Get(
        path: '/api/v1/groups',
        operationId: 'listGroups',
        tags: ['Groups'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Groups visible to the authenticated user.', content: new OA\JsonContent(ref: '#/components/schemas/GroupCollection')),
            new OA\Response(response: 401, description: 'Authentication is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function index(Request $request): GroupCollectionData
    {
        /** @var User $user */
        $user = $request->user();
        $baseQuery = Group::query()->visibleTo($user);
        $groups = QueryBuilder::for($baseQuery)
            ->allowedFilters('name')
            ->allowedIncludes()
            ->allowedSorts('name', 'created_at')
            ->allowedFields('groups.id', 'groups.name', 'groups.description', 'groups.created_by')
            ->defaultSort('-created_at')
            ->paginate();

        /** @var DataCollection<int, GroupData> $data */
        $data = GroupData::collect($groups->items(), DataCollection::class);

        return new GroupCollectionData(
            data: $data,
            meta: new PaginationMetaData($groups->currentPage(), $groups->lastPage(), $groups->perPage(), $groups->total()),
        );
    }

    #[Authorize('create', Group::class)]
    #[OA\Post(
        path: '/api/v1/groups',
        operationId: 'createGroup',
        tags: ['Groups'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/CreateGroupRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Group created.', content: new OA\JsonContent(ref: '#/components/schemas/Group')),
            new OA\Response(response: 401, description: 'Authentication is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 422, description: 'The request payload is invalid.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function store(CreateGroupData $data, Request $request, CreateGroup $createGroup): GroupData
    {
        /** @var User $user */
        $user = $request->user();

        return GroupData::from($createGroup->handle($user, $data->name, $data->description));
    }

    #[Authorize('view', 'group')]
    #[OA\Get(
        path: '/api/v1/groups/{group}',
        operationId: 'getGroup',
        tags: ['Groups'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\PathParameter(name: 'group', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Group details.', content: new OA\JsonContent(ref: '#/components/schemas/Group')),
            new OA\Response(response: 401, description: 'Authentication is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'The group is not visible to this user.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function show(Group $group): GroupData
    {
        return GroupData::from($group);
    }

    #[Authorize('update', 'group')]
    #[OA\Patch(
        path: '/api/v1/groups/{group}', operationId: 'updateGroup', tags: ['Groups'], security: [['bearerAuth' => []]],
        parameters: [new OA\PathParameter(name: 'group', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UpdateGroupRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Group updated.', content: new OA\JsonContent(ref: '#/components/schemas/Group')),
            new OA\Response(response: 401, description: 'Authentication is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 403, description: 'An active administrator is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'The group is not visible.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 422, description: 'The request payload is invalid.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function update(UpdateGroupData $data, Group $group): GroupData
    {
        $changes = [];

        if (! $data->name instanceof Optional) {
            $changes['name'] = $data->name;
        }

        if (! $data->description instanceof Optional) {
            $changes['description'] = $data->description;
        }

        $group->update($changes);

        return GroupData::from($group->refresh());
    }

    #[Authorize('delete', 'group')]
    #[OA\Delete(
        path: '/api/v1/groups/{group}', operationId: 'deleteGroup', tags: ['Groups'], security: [['bearerAuth' => []]],
        parameters: [new OA\PathParameter(name: 'group', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 204, description: 'Group soft deleted.'),
            new OA\Response(response: 401, description: 'Authentication is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 403, description: 'An active administrator is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'The group is not visible.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function destroy(Group $group): Response
    {
        $group->delete();

        return response()->noContent();
    }
}

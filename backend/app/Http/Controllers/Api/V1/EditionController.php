<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Editions\CreateEdition;
use App\Data\Api\V1\Editions\CreateEditionData;
use App\Data\Api\V1\Editions\EditionCollectionData;
use App\Data\Api\V1\Editions\EditionData;
use App\Data\Api\V1\Editions\UpdateEditionData;
use App\Data\Api\V1\Shared\PaginationMetaData;
use App\Enums\EditionStatus;
use App\Exceptions\DomainConflictException;
use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use OpenApi\Attributes as OA;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Optional;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

#[OA\Tag(name: 'Editions')]
final class EditionController extends Controller
{
    #[Authorize('viewAny', [Edition::class, 'group'])]
    #[OA\Get(
        path: '/api/v1/groups/{group}/editions', operationId: 'listEditions', tags: ['Editions'], security: [['bearerAuth' => []]],
        parameters: [new OA\PathParameter(name: 'group', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Editions in the group.', content: new OA\JsonContent(ref: '#/components/schemas/EditionCollection')),
            new OA\Response(response: 401, description: 'Authentication is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'The group is not visible.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function index(Group $group): EditionCollectionData
    {
        $baseQuery = Edition::query()->whereBelongsTo($group);
        $editions = QueryBuilder::for($baseQuery)
            ->allowedFilters('name', AllowedFilter::exact('status'), AllowedFilter::exact('type'))
            ->allowedIncludes()
            ->allowedSorts('name', 'event_date', 'created_at')
            ->allowedFields('editions.id', 'editions.group_id', 'editions.name', 'editions.type', 'editions.status', 'editions.budget_cents', 'editions.currency', 'editions.event_date', 'editions.settings', 'editions.drawn_at', 'editions.revealed_at', 'editions.created_by')
            ->defaultSort('-created_at')
            ->paginate();

        /** @var DataCollection<int, EditionData> $data */
        $data = EditionData::collect($editions->items(), DataCollection::class);

        return new EditionCollectionData(
            $data,
            new PaginationMetaData($editions->currentPage(), $editions->lastPage(), $editions->perPage(), $editions->total()),
        );
    }

    #[Authorize('create', [Edition::class, 'group'])]
    #[OA\Post(
        path: '/api/v1/groups/{group}/editions', operationId: 'createEdition', tags: ['Editions'], security: [['bearerAuth' => []]],
        parameters: [new OA\PathParameter(name: 'group', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/CreateEditionRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Draft edition created.', content: new OA\JsonContent(ref: '#/components/schemas/Edition')),
            new OA\Response(response: 401, description: 'Authentication is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 403, description: 'An active administrator is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'The group is not visible.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 422, description: 'The request payload is invalid.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function store(CreateEditionData $data, Group $group, Request $request, CreateEdition $createEdition): EditionData
    {
        /** @var User $user */
        $user = $request->user();

        return EditionData::from($createEdition->handle($group, $user, $data->name, $data->budgetCents, $data->eventDate, $data->settings));
    }

    #[Authorize('view', 'edition')]
    #[OA\Get(
        path: '/api/v1/groups/{group}/editions/{edition}', operationId: 'getEdition', tags: ['Editions'], security: [['bearerAuth' => []]],
        parameters: [new OA\PathParameter(name: 'group', required: true, schema: new OA\Schema(type: 'integer')), new OA\PathParameter(name: 'edition', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Edition details.', content: new OA\JsonContent(ref: '#/components/schemas/Edition')),
            new OA\Response(response: 401, description: 'Authentication is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'The group or edition is not visible.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function show(Group $group, Edition $edition): EditionData
    {
        return EditionData::from($edition);
    }

    #[Authorize('update', 'edition')]
    #[OA\Patch(
        path: '/api/v1/groups/{group}/editions/{edition}', operationId: 'updateEdition', tags: ['Editions'], security: [['bearerAuth' => []]],
        parameters: [new OA\PathParameter(name: 'group', required: true, schema: new OA\Schema(type: 'integer')), new OA\PathParameter(name: 'edition', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UpdateEditionRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Edition updated.', content: new OA\JsonContent(ref: '#/components/schemas/Edition')),
            new OA\Response(response: 401, description: 'Authentication is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 403, description: 'An active administrator is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'The group or edition is not visible.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 409, description: 'The edition can no longer be edited.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 422, description: 'The request payload is invalid.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function update(UpdateEditionData $data, Group $group, Edition $edition): EditionData
    {
        if (! in_array($edition->status, [EditionStatus::Draft, EditionStatus::Open], true)) {
            throw new DomainConflictException('editions.edition_frozen');
        }

        $changes = [];

        foreach (['name', 'budgetCents', 'eventDate', 'settings'] as $property) {
            if (! $data->{$property} instanceof Optional) {
                $changes[match ($property) {
                    'budgetCents' => 'budget_cents',
                    'eventDate' => 'event_date',
                    default => $property,
                }] = $data->{$property};
            }
        }

        $edition->update($changes);

        return EditionData::from($edition->refresh());
    }

    #[Authorize('delete', 'edition')]
    #[OA\Delete(
        path: '/api/v1/groups/{group}/editions/{edition}', operationId: 'deleteEdition', tags: ['Editions'], security: [['bearerAuth' => []]],
        parameters: [new OA\PathParameter(name: 'group', required: true, schema: new OA\Schema(type: 'integer')), new OA\PathParameter(name: 'edition', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 204, description: 'Draft edition deleted.'),
            new OA\Response(response: 401, description: 'Authentication is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 403, description: 'An active administrator is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'The group or edition is not visible.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 409, description: 'Only draft editions can be deleted.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function destroy(Group $group, Edition $edition): Response
    {
        if ($edition->status !== EditionStatus::Draft) {
            throw new DomainConflictException('editions.draft_delete_only');
        }

        $edition->delete();

        return response()->noContent();
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Editions\AddEditionParticipant;
use App\Actions\Editions\RemoveEditionParticipant;
use App\Data\Api\V1\Editions\AddParticipantData;
use App\Data\Api\V1\Editions\EditionParticipantCollectionData;
use App\Data\Api\V1\Editions\EditionParticipantData;
use App\Data\Api\V1\Groups\GroupMemberData;
use App\Data\Api\V1\Shared\PaginationMetaData;
use App\Enums\GroupMemberRole;
use App\Enums\GroupMemberStatus;
use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\EditionParticipant;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use OpenApi\Attributes as OA;
use Spatie\LaravelData\DataCollection;
use Spatie\QueryBuilder\QueryBuilder;

#[OA\Tag(name: 'Edition participants')]
final class EditionParticipantController extends Controller
{
    #[Authorize('viewAny', [EditionParticipant::class, 'edition'])]
    #[OA\Get(
        path: '/api/v1/groups/{group}/editions/{edition}/participants', operationId: 'listEditionParticipants', tags: ['Edition participants'], security: [['bearerAuth' => []]],
        parameters: [new OA\PathParameter(name: 'group', required: true, schema: new OA\Schema(type: 'integer')), new OA\PathParameter(name: 'edition', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Edition roster.', content: new OA\JsonContent(ref: '#/components/schemas/EditionParticipantCollection')),
            new OA\Response(response: 401, description: 'Authentication is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'The group or edition is not visible.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function index(Group $group, Edition $edition, Request $request): EditionParticipantCollectionData
    {
        /** @var User $viewer */
        $viewer = $request->user();
        $viewerIsAdmin = $group->members()
            ->whereBelongsTo($viewer)
            ->where('status', GroupMemberStatus::Active)
            ->where('role', GroupMemberRole::Admin)
            ->exists();
        $currentParticipantId = $edition->participants()
            ->whereHas('groupMember', fn ($members) => $members
                ->whereBelongsTo($viewer)
                ->where('status', GroupMemberStatus::Active))
            ->value('id');
        $baseQuery = EditionParticipant::query()->whereBelongsTo($edition);
        $participants = QueryBuilder::for($baseQuery)
            ->allowedFilters()
            ->allowedIncludes()
            ->allowedSorts('created_at')
            ->allowedFields('edition_participants.id', 'edition_participants.edition_id', 'edition_participants.group_id', 'edition_participants.group_member_id')
            ->defaultSort('created_at')
            ->with('groupMember')
            ->paginate();

        $items = $participants->getCollection()->map(function (EditionParticipant $participant) use ($viewer, $viewerIsAdmin): EditionParticipantData {
            $member = $participant->getRelation('groupMember');

            if (! $member instanceof GroupMember) {
                throw new \LogicException('An edition participant must reference a group member.');
            }

            return new EditionParticipantData(
                id: $participant->id,
                editionId: $participant->edition_id,
                groupMember: GroupMemberData::forViewer($member, $viewer, $viewerIsAdmin),
            );
        });
        /** @var DataCollection<int, EditionParticipantData> $data */
        $data = EditionParticipantData::collect($items, DataCollection::class);

        return new EditionParticipantCollectionData(
            $data,
            new PaginationMetaData($participants->currentPage(), $participants->lastPage(), $participants->perPage(), $participants->total()),
            is_int($currentParticipantId) ? $currentParticipantId : null,
        );
    }

    #[Authorize('create', [EditionParticipant::class, 'edition'])]
    #[OA\Post(
        path: '/api/v1/groups/{group}/editions/{edition}/participants', operationId: 'addEditionParticipant', tags: ['Edition participants'], security: [['bearerAuth' => []]],
        parameters: [new OA\PathParameter(name: 'group', required: true, schema: new OA\Schema(type: 'integer')), new OA\PathParameter(name: 'edition', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/AddParticipantRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Participant added.', content: new OA\JsonContent(ref: '#/components/schemas/EditionParticipant')),
            new OA\Response(response: 401, description: 'Authentication is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 403, description: 'An active administrator is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'The group, edition, or member is not visible.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 409, description: 'The roster is frozen or member is unavailable.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 422, description: 'The request payload is invalid.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function store(AddParticipantData $data, Group $group, Edition $edition, Request $request, AddEditionParticipant $addParticipant): EditionParticipantData
    {
        /** @var User $viewer */
        $viewer = $request->user();
        $member = $group->members()->findOrFail($data->groupMemberId);
        $participant = $addParticipant->handle($edition, $member);

        return new EditionParticipantData(
            $participant->id,
            $participant->edition_id,
            GroupMemberData::forViewer($member, $viewer, viewerIsAdmin: true),
        );
    }

    #[Authorize('delete', 'participant')]
    #[OA\Delete(
        path: '/api/v1/groups/{group}/editions/{edition}/participants/{participant}', operationId: 'removeEditionParticipant', tags: ['Edition participants'], security: [['bearerAuth' => []]],
        parameters: [new OA\PathParameter(name: 'group', required: true, schema: new OA\Schema(type: 'integer')), new OA\PathParameter(name: 'edition', required: true, schema: new OA\Schema(type: 'integer')), new OA\PathParameter(name: 'participant', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 204, description: 'Participant removed.'),
            new OA\Response(response: 401, description: 'Authentication is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 403, description: 'An active administrator is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'The group, edition, or participant is not visible.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 409, description: 'The roster is frozen, the open-edition minimum would be violated, or the participant has protected purchase or message history.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function destroy(Group $group, Edition $edition, EditionParticipant $participant, RemoveEditionParticipant $removeParticipant): Response
    {
        $removeParticipant->handle($participant);

        return response()->noContent();
    }
}

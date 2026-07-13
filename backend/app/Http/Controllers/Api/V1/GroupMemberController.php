<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Groups\DeactivateGroupMember;
use App\Actions\Groups\IssueInvitation;
use App\Actions\Groups\ReactivateGroupMember;
use App\Actions\Groups\UpdateGroupMember;
use App\Data\Api\V1\Groups\CreateGroupMemberData;
use App\Data\Api\V1\Groups\GroupMemberCollectionData;
use App\Data\Api\V1\Groups\GroupMemberData;
use App\Data\Api\V1\Groups\IssuedInvitationData;
use App\Data\Api\V1\Groups\UpdateGroupMemberData;
use App\Data\Api\V1\Shared\PaginationMetaData;
use App\Enums\GroupMemberRole;
use App\Enums\GroupMemberStatus;
use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use OpenApi\Attributes as OA;
use Spatie\LaravelData\DataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

#[OA\Tag(name: 'Group members')]
final class GroupMemberController extends Controller
{
    #[Authorize('viewAny', [GroupMember::class, 'group'])]
    #[OA\Get(
        path: '/api/v1/groups/{group}/members', operationId: 'listGroupMembers', tags: ['Group members'], security: [['bearerAuth' => []]],
        parameters: [new OA\PathParameter(name: 'group', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Members in the group.', content: new OA\JsonContent(ref: '#/components/schemas/GroupMemberCollection')),
            new OA\Response(response: 401, description: 'Authentication is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'The group is not visible.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function index(Group $group, Request $request): GroupMemberCollectionData
    {
        /** @var User $viewer */
        $viewer = $request->user();
        $viewerIsAdmin = $group->members()
            ->whereBelongsTo($viewer)
            ->where('status', GroupMemberStatus::Active)
            ->where('role', GroupMemberRole::Admin)
            ->exists();
        $baseQuery = GroupMember::query()->whereBelongsTo($group);
        $members = QueryBuilder::for($baseQuery)
            ->allowedFilters('display_name', AllowedFilter::exact('status'), AllowedFilter::exact('role'))
            ->allowedIncludes()
            ->allowedSorts('display_name', 'created_at')
            ->allowedFields('group_members.id', 'group_members.group_id', 'group_members.user_id', 'group_members.display_name', 'group_members.email', 'group_members.role', 'group_members.status')
            ->defaultSort('display_name')
            ->paginate();

        $items = $members->getCollection()->map(
            fn (GroupMember $member): GroupMemberData => GroupMemberData::forViewer($member, $viewer, $viewerIsAdmin),
        );
        /** @var DataCollection<int, GroupMemberData> $data */
        $data = GroupMemberData::collect($items, DataCollection::class);

        return new GroupMemberCollectionData(
            $data,
            new PaginationMetaData($members->currentPage(), $members->lastPage(), $members->perPage(), $members->total()),
        );
    }

    #[Authorize('create', [GroupMember::class, 'group'])]
    #[OA\Post(
        path: '/api/v1/groups/{group}/members', operationId: 'createGroupMember', tags: ['Group members'], security: [['bearerAuth' => []]],
        parameters: [new OA\PathParameter(name: 'group', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/CreateGroupMemberRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Placeholder member created.', content: new OA\JsonContent(ref: '#/components/schemas/GroupMember')),
            new OA\Response(response: 401, description: 'Authentication is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 403, description: 'An active administrator is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'The group is not visible.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 422, description: 'The request payload is invalid.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function store(CreateGroupMemberData $data, Group $group, Request $request): GroupMemberData
    {
        $member = $group->members()->create([
            'display_name' => $data->displayName,
            'email' => $data->email,
            'role' => $data->role,
            'status' => GroupMemberStatus::Invited,
        ]);

        /** @var User $viewer */
        $viewer = $request->user();

        return GroupMemberData::forViewer($member, $viewer, viewerIsAdmin: true);
    }

    #[Authorize('update', 'member')]
    #[OA\Patch(
        path: '/api/v1/groups/{group}/members/{member}', operationId: 'updateGroupMember', tags: ['Group members'], security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'group', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'member', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UpdateGroupMemberRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Member updated.', content: new OA\JsonContent(ref: '#/components/schemas/GroupMember')),
            new OA\Response(response: 401, description: 'Authentication is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 403, description: 'The member cannot update these fields.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'The group or member is not visible.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 409, description: 'The last active administrator cannot be demoted.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 422, description: 'The request payload is invalid.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function update(UpdateGroupMemberData $data, Group $group, GroupMember $member, Request $request, UpdateGroupMember $updateMember): GroupMemberData
    {
        /** @var User $user */
        $user = $request->user();

        $updatedMember = $updateMember->handle($user, $member, $data->displayName, $data->email, $data->role);

        return GroupMemberData::forViewer($updatedMember, $user, $member->user_id !== $user->id);
    }

    #[Authorize('deactivate', 'member')]
    #[OA\Put(
        path: '/api/v1/groups/{group}/members/{member}/deactivate', operationId: 'deactivateGroupMember', tags: ['Group members'], security: [['bearerAuth' => []]],
        parameters: [new OA\PathParameter(name: 'group', required: true, schema: new OA\Schema(type: 'integer')), new OA\PathParameter(name: 'member', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Member deactivated.', content: new OA\JsonContent(ref: '#/components/schemas/GroupMember')),
            new OA\Response(response: 401, description: 'Authentication is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 403, description: 'The operation is not allowed.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'The group or member is not visible.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 409, description: 'The member is required by an editable roster or is the last administrator.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function deactivate(Group $group, GroupMember $member, Request $request, DeactivateGroupMember $deactivateMember): GroupMemberData
    {
        /** @var User $viewer */
        $viewer = $request->user();

        return GroupMemberData::forViewer($deactivateMember->handle($member), $viewer, $member->user_id !== $viewer->id);
    }

    #[Authorize('reactivate', 'member')]
    #[OA\Put(
        path: '/api/v1/groups/{group}/members/{member}/reactivate', operationId: 'reactivateGroupMember', tags: ['Group members'], security: [['bearerAuth' => []]],
        parameters: [new OA\PathParameter(name: 'group', required: true, schema: new OA\Schema(type: 'integer')), new OA\PathParameter(name: 'member', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Member reactivated.', content: new OA\JsonContent(ref: '#/components/schemas/GroupMember')),
            new OA\Response(response: 401, description: 'Authentication is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 403, description: 'An active administrator is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'The group or member is not visible.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function reactivate(Group $group, GroupMember $member, Request $request, ReactivateGroupMember $reactivateMember): GroupMemberData
    {
        /** @var User $viewer */
        $viewer = $request->user();

        return GroupMemberData::forViewer($reactivateMember->handle($member), $viewer, viewerIsAdmin: true);
    }

    #[Authorize('invite', 'member')]
    #[OA\Post(
        path: '/api/v1/groups/{group}/members/{member}/invite', operationId: 'issueGroupInvitation', tags: ['Group members'], security: [['bearerAuth' => []]],
        parameters: [new OA\PathParameter(name: 'group', required: true, schema: new OA\Schema(type: 'integer')), new OA\PathParameter(name: 'member', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 201, description: 'Invitation issued or rotated.', content: new OA\JsonContent(ref: '#/components/schemas/IssuedInvitation')),
            new OA\Response(response: 401, description: 'Authentication is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 403, description: 'An active administrator is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'The group or member is not visible.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 409, description: 'The member has already claimed an account.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function invite(Group $group, GroupMember $member, Request $request, IssueInvitation $issueInvitation): IssuedInvitationData
    {
        /** @var User $viewer */
        $viewer = $request->user();
        $issued = $issueInvitation->handle($member);

        return new IssuedInvitationData(
            member: GroupMemberData::forViewer($issued->member, $viewer, viewerIsAdmin: true),
            inviteToken: $issued->token,
            expiresAt: $issued->expiresAt->toIso8601String(),
        );
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Data\Api\V1\Draws\AssignmentCollectionData;
use App\Data\Api\V1\Draws\AssignmentData;
use App\Data\Api\V1\Draws\AssignmentParticipantData;
use App\Data\Api\V1\Draws\MyAssignmentData;
use App\Data\Api\V1\Wishes\WishData;
use App\Draws\DrawConflictException;
use App\Draws\DrawFailureCode;
use App\Enums\EditionStatus;
use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Edition;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use OpenApi\Attributes as OA;
use Spatie\LaravelData\DataCollection;

#[OA\Tag(name: 'Assignments')]
final class AssignmentController extends Controller
{
    #[Authorize('viewOwnAssignment', 'edition')]
    #[OA\Get(
        path: '/api/v1/groups/{group}/editions/{edition}/my-assignment', operationId: 'getMyAssignment', tags: ['Assignments'], security: [['bearerAuth' => []]],
        parameters: [new OA\PathParameter(name: 'group', required: true, schema: new OA\Schema(type: 'integer')), new OA\PathParameter(name: 'edition', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Only the authenticated giver recipient.', content: new OA\JsonContent(ref: '#/components/schemas/MyAssignment')),
            new OA\Response(response: 401, description: 'Authentication is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 403, description: 'An active claimed participant is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'The assignment is not available.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 409, description: 'The edition has not been drawn.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function mine(Group $group, Edition $edition, Request $request): MyAssignmentData
    {
        if (! in_array($edition->status, [EditionStatus::Drawn, EditionStatus::Revealed, EditionStatus::Archived], true)) {
            throw new DrawConflictException(DrawFailureCode::AssignmentNotAvailable);
        }

        /** @var User $user */
        $user = $request->user();
        $giver = $edition->participants()
            ->whereHas('groupMember', fn ($members) => $members->whereBelongsTo($user))
            ->firstOrFail();
        $assignment = Assignment::query()
            ->whereBelongsTo($edition)
            ->whereBelongsTo($giver, 'giver')
            ->with(['receiver.groupMember.user', 'receiver.wishes'])
            ->firstOrFail();
        $receiver = $assignment->receiver;

        if ($receiver === null) {
            throw new DrawConflictException(DrawFailureCode::CorruptAssignments);
        }

        /** @var DataCollection<int, WishData> $wishes */
        $wishes = WishData::collect($receiver->wishes, DataCollection::class);

        return new MyAssignmentData(AssignmentParticipantData::fromParticipant($receiver), $wishes);
    }

    #[Authorize('viewAssignments', 'edition')]
    #[OA\Get(
        path: '/api/v1/groups/{group}/editions/{edition}/assignments', operationId: 'listAssignments', tags: ['Assignments'], security: [['bearerAuth' => []]],
        parameters: [new OA\PathParameter(name: 'group', required: true, schema: new OA\Schema(type: 'integer')), new OA\PathParameter(name: 'edition', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Revealed assignment mappings.', content: new OA\JsonContent(ref: '#/components/schemas/AssignmentCollection')),
            new OA\Response(response: 401, description: 'Authentication is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 403, description: 'An active participant or administrator is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'The edition is not visible.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 409, description: 'Assignments have not been revealed.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function index(Group $group, Edition $edition): AssignmentCollectionData
    {
        if (! in_array($edition->status, [EditionStatus::Revealed, EditionStatus::Archived], true)) {
            throw new DrawConflictException(DrawFailureCode::AssignmentsNotRevealed);
        }

        $assignments = $edition->assignments()->with(['giver.groupMember.user', 'receiver.groupMember.user'])->orderBy('id')->get();
        $items = $assignments->map(function (Assignment $assignment): AssignmentData {
            $giver = $assignment->giver;
            $receiver = $assignment->receiver;

            if ($giver === null || $receiver === null) {
                throw new DrawConflictException(DrawFailureCode::CorruptAssignments);
            }

            return new AssignmentData(
                AssignmentParticipantData::fromParticipant($giver),
                AssignmentParticipantData::fromParticipant($receiver),
            );
        });
        /** @var DataCollection<int, AssignmentData> $data */
        $data = AssignmentData::collect($items, DataCollection::class);

        return new AssignmentCollectionData($data);
    }
}

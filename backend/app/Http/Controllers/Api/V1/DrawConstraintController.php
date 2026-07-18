<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\DrawConstraints\CopyDrawConstraintsFromPreviousEdition;
use App\Actions\DrawConstraints\CreateDrawConstraint;
use App\Actions\DrawConstraints\DeleteDrawConstraint;
use App\Data\Api\V1\Draws\CopiedDrawConstraintsData;
use App\Data\Api\V1\Draws\CreateDrawConstraintData;
use App\Data\Api\V1\Draws\DrawConstraintCollectionData;
use App\Data\Api\V1\Draws\DrawConstraintData;
use App\Http\Controllers\Controller;
use App\Models\DrawConstraint;
use App\Models\Edition;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use OpenApi\Attributes as OA;
use Spatie\LaravelData\DataCollection;

#[OA\Tag(name: 'Draw constraints')]
final class DrawConstraintController extends Controller
{
    #[Authorize('manageDraw', 'edition')]
    #[OA\Get(
        path: '/api/v1/groups/{group}/editions/{edition}/draw-constraints', operationId: 'listDrawConstraints', tags: ['Draw constraints'], security: [['bearerAuth' => []]],
        parameters: [new OA\PathParameter(name: 'group', required: true, schema: new OA\Schema(type: 'integer')), new OA\PathParameter(name: 'edition', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Private draw constraints.', content: new OA\JsonContent(ref: '#/components/schemas/DrawConstraintCollection')),
            new OA\Response(response: 401, description: 'Authentication is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 403, description: 'An active administrator is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'The group or edition is not visible.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function index(Group $group, Edition $edition): DrawConstraintCollectionData
    {
        /** @var DataCollection<int, DrawConstraintData> $data */
        $data = DrawConstraintData::collect($edition->drawConstraints()->latest()->get(), DataCollection::class);

        return new DrawConstraintCollectionData($data);
    }

    #[Authorize('manageDraw', 'edition')]
    #[OA\Post(
        path: '/api/v1/groups/{group}/editions/{edition}/draw-constraints', operationId: 'createDrawConstraint', tags: ['Draw constraints'], security: [['bearerAuth' => []]],
        parameters: [new OA\PathParameter(name: 'group', required: true, schema: new OA\Schema(type: 'integer')), new OA\PathParameter(name: 'edition', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/CreateDrawConstraintRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Constraint created.', content: new OA\JsonContent(ref: '#/components/schemas/DrawConstraint')),
            new OA\Response(response: 401, description: 'Authentication is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 403, description: 'An active administrator is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'The group, edition, or participant is not visible.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 409, description: 'The constraint conflicts or makes the draw impossible.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 422, description: 'The request payload is invalid.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function store(CreateDrawConstraintData $data, Group $group, Edition $edition, Request $request, CreateDrawConstraint $create): DrawConstraintData
    {
        /** @var User $user */
        $user = $request->user();
        $giver = $edition->participants()->findOrFail($data->giverParticipantId);
        $receiver = $edition->participants()->findOrFail($data->receiverParticipantId);

        return DrawConstraintData::from($create->handle($edition, $user, $data->type, $giver, $receiver));
    }

    #[Authorize('manageDraw', 'edition')]
    #[OA\Post(
        path: '/api/v1/groups/{group}/editions/{edition}/draw-constraints/copy-from-previous', operationId: 'copyDrawConstraintsFromPreviousEdition', tags: ['Draw constraints'], security: [['bearerAuth' => []]],
        parameters: [new OA\PathParameter(name: 'group', required: true, schema: new OA\Schema(type: 'integer')), new OA\PathParameter(name: 'edition', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 201, description: 'Recurring exclusions copied from the previous edition.', content: new OA\JsonContent(ref: '#/components/schemas/CopiedDrawConstraints')),
            new OA\Response(response: 401, description: 'Authentication is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 403, description: 'An active administrator is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'The group or edition is not visible.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 409, description: 'The edition can no longer be changed.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function copyFromPrevious(Group $group, Edition $edition, Request $request, CopyDrawConstraintsFromPreviousEdition $copy): CopiedDrawConstraintsData
    {
        /** @var User $user */
        $user = $request->user();

        return CopiedDrawConstraintsData::fromResult($copy->handle($edition, $user));
    }

    #[Authorize('manageDraw', 'edition')]
    #[OA\Delete(
        path: '/api/v1/groups/{group}/editions/{edition}/draw-constraints/{drawConstraint}', operationId: 'deleteDrawConstraint', tags: ['Draw constraints'], security: [['bearerAuth' => []]],
        parameters: [new OA\PathParameter(name: 'group', required: true, schema: new OA\Schema(type: 'integer')), new OA\PathParameter(name: 'edition', required: true, schema: new OA\Schema(type: 'integer')), new OA\PathParameter(name: 'drawConstraint', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 204, description: 'Constraint deleted.'),
            new OA\Response(response: 401, description: 'Authentication is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 403, description: 'An active administrator is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'The constraint is not visible.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 409, description: 'The edition can no longer be changed.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function destroy(Group $group, Edition $edition, DrawConstraint $drawConstraint, DeleteDrawConstraint $delete): Response
    {
        $delete->handle($drawConstraint);

        return response()->noContent();
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Notifications\RegisterPushDevice;
use App\Data\Api\V1\Notifications\PushDeviceData;
use App\Data\Api\V1\Notifications\RegisterPushDeviceData;
use App\Http\Controllers\Controller;
use App\Models\PushDevice;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Push devices')]
final class PushDeviceController extends Controller
{
    #[Authorize('create', PushDevice::class)]
    #[OA\Post(
        path: '/api/v1/push-devices', operationId: 'registerPushDevice', tags: ['Push devices'], security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/RegisterPushDeviceRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Push device registered or refreshed.', content: new OA\JsonContent(ref: '#/components/schemas/PushDevice')),
            new OA\Response(response: 401, description: 'Authentication is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 422, description: 'The request payload is invalid.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function store(RegisterPushDeviceData $data, Request $request, RegisterPushDevice $registerPushDevice): PushDeviceData
    {
        /** @var User $user */
        $user = $request->user();

        return PushDeviceData::fromPushDevice($registerPushDevice->handle(
            $user,
            $data->expoPushToken,
            $data->platform,
            $data->deviceName,
        ));
    }

    #[Authorize('delete', 'pushDevice')]
    #[OA\Delete(
        path: '/api/v1/push-devices/{pushDevice}', operationId: 'deletePushDevice', tags: ['Push devices'], security: [['bearerAuth' => []]],
        parameters: [new OA\PathParameter(name: 'pushDevice', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 204, description: 'Push device removed.'),
            new OA\Response(response: 401, description: 'Authentication is required.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 403, description: 'Only the device owner may remove it.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function destroy(PushDevice $pushDevice): Response
    {
        $pushDevice->delete();

        return response()->noContent();
    }
}

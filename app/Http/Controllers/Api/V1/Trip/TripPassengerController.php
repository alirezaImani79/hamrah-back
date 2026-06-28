<?php

namespace App\Http\Controllers\Api\V1\Trip;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\TripResource;
use App\Models\Trip;
use App\Services\Trip\TripService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class TripPassengerController extends Controller
{
    public function __construct(private TripService $trips) {}

    /**
     * Join a trip as a passenger.
     */
    #[OA\Post(
        path: '/api/v1/trips/{trip}/join',
        operationId: 'joinTrip',
        summary: 'Join a trip as a passenger',
        tags: ['Trips'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'trip', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Joined the trip', content: new OA\JsonContent(ref: '#/components/schemas/TripResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Cannot join (own trip or already departed)', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 404, description: 'Trip not found', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 422, description: 'Trip full or already joined', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ],
    )]
    public function store(Request $request, string $trip): JsonResponse
    {
        $model = Trip::findOrFail($trip);
        $this->authorize('join', $model);

        $this->trips->addPassenger($model, $request->user());

        return ApiResponse::success(new TripResource($model->loadCount('passengers')), 'Joined the trip.');
    }

    /**
     * Leave a trip you previously joined.
     */
    #[OA\Delete(
        path: '/api/v1/trips/{trip}/leave',
        operationId: 'leaveTrip',
        summary: 'Leave a trip',
        tags: ['Trips'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'trip', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Left the trip', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess')),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'You are not a passenger on this trip', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 404, description: 'Trip not found', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ],
    )]
    public function destroy(Request $request, string $trip): JsonResponse
    {
        $model = Trip::findOrFail($trip);
        $this->authorize('leave', $model);

        $this->trips->removePassenger($model, $request->user());

        return ApiResponse::success(null, 'Left the trip.');
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Trip;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Trip\StoreTripRequest;
use App\Http\Requests\Api\V1\Trip\UpdateTripRequest;
use App\Http\Resources\V1\TripResource;
use App\Models\Trip;
use App\Services\Trip\TripService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class TripController extends Controller
{
    public function __construct(private TripService $trips) {}

    /**
     * List the trips the authenticated user drives.
     */
    #[OA\Get(
        path: '/api/v1/trips',
        operationId: 'listTrips',
        summary: 'List the authenticated user\'s trips',
        tags: ['Trips'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'List of trips', content: new OA\JsonContent(ref: '#/components/schemas/TripCollectionResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ],
    )]
    public function index(Request $request): JsonResponse
    {
        $trips = $request->user()->trips()->withCount('passengers')->latest()->get();

        return ApiResponse::success(TripResource::collection($trips), 'Trips retrieved.');
    }

    /**
     * Create a new trip for the authenticated user.
     */
    #[OA\Post(
        path: '/api/v1/trips',
        operationId: 'createTrip',
        summary: 'Create a trip',
        tags: ['Trips'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StoreTripInput'),
        ),
        responses: [
            new OA\Response(response: 201, description: 'Trip created', content: new OA\JsonContent(ref: '#/components/schemas/TripResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ],
    )]
    public function store(StoreTripRequest $request): JsonResponse
    {
        $trip = $this->trips->create($request->user(), $request->validated());

        return ApiResponse::success(new TripResource($trip->loadCount('passengers')), 'Trip created.', 201);
    }

    /**
     * Show a trip the user drives or has joined.
     */
    #[OA\Get(
        path: '/api/v1/trips/{trip}',
        operationId: 'showTrip',
        summary: 'Show a trip',
        tags: ['Trips'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'trip', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Trip details', content: new OA\JsonContent(ref: '#/components/schemas/TripResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 404, description: 'Trip not found', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ],
    )]
    public function show(Request $request, string $trip): JsonResponse
    {
        $model = Trip::findOrFail($trip);
        $this->authorize('view', $model);

        return ApiResponse::success(new TripResource($model->loadCount('passengers')), 'Trip retrieved.');
    }

    /**
     * Update a trip the user drives, then notify its passengers.
     */
    #[OA\Put(
        path: '/api/v1/trips/{trip}',
        operationId: 'updateTrip',
        summary: 'Update a trip',
        tags: ['Trips'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'trip', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UpdateTripInput'),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Trip updated', content: new OA\JsonContent(ref: '#/components/schemas/TripResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Forbidden or trip already departed', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 404, description: 'Trip not found', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ],
    )]
    public function update(UpdateTripRequest $request, string $trip): JsonResponse
    {
        $model = Trip::findOrFail($trip);
        $this->authorize('update', $model);

        $updated = $this->trips->update($model, $request->validated());
        $this->trips->notifyPassengersOfUpdate($updated);

        return ApiResponse::success(new TripResource($updated->loadCount('passengers')), 'Trip updated.');
    }

    /**
     * Delete a trip the user drives.
     */
    #[OA\Delete(
        path: '/api/v1/trips/{trip}',
        operationId: 'deleteTrip',
        summary: 'Delete a trip',
        tags: ['Trips'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'trip', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Trip deleted', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess')),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 404, description: 'Trip not found', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ],
    )]
    public function destroy(Request $request, string $trip): JsonResponse
    {
        $model = Trip::findOrFail($trip);
        $this->authorize('delete', $model);

        $this->trips->delete($model);

        return ApiResponse::success(null, 'Trip deleted.');
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Trip;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Trip\MatchTripRequest;
use App\Http\Requests\Api\V1\Trip\StoreTripRequest;
use App\Http\Requests\Api\V1\Trip\UpdateTripRequest;
use App\Http\Resources\V1\MatchedTripResource;
use App\Http\Resources\V1\TripDetailResource;
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
            new OA\Response(response: 401, description: 'Unauthenticated (`UNAUTHENTICATED`).', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ],
    )]
    public function index(Request $request): JsonResponse
    {
        $trips = $request->user()->trips()->withCount('passengers')->latest()->get();

        return ApiResponse::success(TripResource::collection($trips), 'Trips retrieved.');
    }

    /**
     * List the authenticated user's scheduled trips, as driver or passenger.
     */
    #[OA\Get(
        path: '/api/v1/trips/upcoming',
        operationId: 'listUpcomingTrips',
        summary: 'List the authenticated user\'s scheduled trips',
        tags: ['Trips'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'List of scheduled trips', content: new OA\JsonContent(ref: '#/components/schemas/TripCollectionResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated (`UNAUTHENTICATED`).', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ],
    )]
    public function upcoming(Request $request): JsonResponse
    {
        $trips = $this->trips->upcomingForUser($request->user());

        return ApiResponse::success(TripResource::collection($trips), 'Upcoming trips retrieved.');
    }

    /**
     * List the authenticated user's ongoing trips, as driver or passenger.
     */
    #[OA\Get(
        path: '/api/v1/trips/ongoing',
        operationId: 'listOngoingTrips',
        summary: 'List the authenticated user\'s ongoing trips',
        tags: ['Trips'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'List of ongoing trips', content: new OA\JsonContent(ref: '#/components/schemas/TripCollectionResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated (`UNAUTHENTICATED`).', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ],
    )]
    public function ongoing(Request $request): JsonResponse
    {
        $trips = $this->trips->ongoingForUser($request->user());

        return ApiResponse::success(TripResource::collection($trips), 'Ongoing trips retrieved.');
    }

    /**
     * List the authenticated user's past trips, as driver or passenger.
     */
    #[OA\Get(
        path: '/api/v1/trips/history',
        operationId: 'listTripHistory',
        summary: 'List the authenticated user\'s past trips',
        tags: ['Trips'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'List of past trips', content: new OA\JsonContent(ref: '#/components/schemas/TripCollectionResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated (`UNAUTHENTICATED`).', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ],
    )]
    public function history(Request $request): JsonResponse
    {
        $trips = $this->trips->historyForUser($request->user());

        return ApiResponse::success(TripResource::collection($trips), 'Trip history retrieved.');
    }

    /**
     * Find scheduled trips that fit the authenticated user's travel request.
     */
    #[OA\Post(
        path: '/api/v1/trips/match',
        operationId: 'matchTrips',
        summary: 'Find scheduled trips matching a travel request',
        tags: ['Trips'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/MatchTripInput'),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Ranked matching trips', content: new OA\JsonContent(ref: '#/components/schemas/MatchedTripCollectionResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated (`UNAUTHENTICATED`).', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 422, description: 'Validation error (`VALIDATION_FAILED`).', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ],
    )]
    public function match(MatchTripRequest $request): JsonResponse
    {
        $trips = $this->trips->matching($request->user(), $request->validated());

        return ApiResponse::success(MatchedTripResource::collection($trips), 'Matching trips retrieved.');
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
            new OA\Response(response: 401, description: 'Unauthenticated (`UNAUTHENTICATED`).', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 422, description: 'Validation error (`VALIDATION_FAILED`).', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
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
            new OA\Response(response: 200, description: 'Trip details with driver, vehicle, and passengers', content: new OA\JsonContent(ref: '#/components/schemas/TripDetailResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated (`UNAUTHENTICATED`).', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Forbidden (`UNAUTHORIZED`).', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 404, description: 'Trip not found (`NOT_FOUND`).', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ],
    )]
    public function show(Request $request, string $trip): JsonResponse
    {
        $model = Trip::findOrFail($trip);
        $this->authorize('view', $model);

        $model->load([
            'user',
            'vehicle',
            'passengers' => fn ($passengers) => $passengers->orderByPivot('created_at'),
        ]);

        return ApiResponse::success(new TripDetailResource($model), 'Trip retrieved.');
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
            new OA\Response(response: 401, description: 'Unauthenticated (`UNAUTHENTICATED`).', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Forbidden, e.g. not the driver or the trip already departed (`UNAUTHORIZED`).', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 404, description: 'Trip not found (`NOT_FOUND`).', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 422, description: 'Validation error (`VALIDATION_FAILED`).', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
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
            new OA\Response(response: 401, description: 'Unauthenticated (`UNAUTHENTICATED`).', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Forbidden (`UNAUTHORIZED`).', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 404, description: 'Trip not found (`NOT_FOUND`).', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ],
    )]
    public function destroy(Request $request, string $trip): JsonResponse
    {
        $model = Trip::findOrFail($trip);
        $this->authorize('delete', $model);

        $this->trips->delete($model);

        return ApiResponse::success(null, 'Trip deleted.');
    }

    /**
     * Start a trip the user drives: scheduled → ongoing.
     */
    #[OA\Post(
        path: '/api/v1/trips/{trip}/start',
        operationId: 'startTrip',
        summary: 'Start a trip',
        tags: ['Trips'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'trip', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Trip started', content: new OA\JsonContent(ref: '#/components/schemas/TripResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated (`UNAUTHENTICATED`).', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Forbidden, only the driver may start the trip (`UNAUTHORIZED`).', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 404, description: 'Trip not found (`NOT_FOUND`).', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 409, description: 'The trip is not scheduled (`TRIP_STATUS_TRANSITION_INVALID`).', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ],
    )]
    public function start(Request $request, string $trip): JsonResponse
    {
        $model = Trip::findOrFail($trip);
        $this->authorize('start', $model);

        $started = $this->trips->start($model);

        return ApiResponse::success(new TripResource($started->loadCount('passengers')), 'Trip started.');
    }

    /**
     * Complete a trip the user drives: ongoing → completed.
     */
    #[OA\Post(
        path: '/api/v1/trips/{trip}/complete',
        operationId: 'completeTrip',
        summary: 'Complete a trip',
        tags: ['Trips'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'trip', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Trip completed', content: new OA\JsonContent(ref: '#/components/schemas/TripResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated (`UNAUTHENTICATED`).', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Forbidden, only the driver may complete the trip (`UNAUTHORIZED`).', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 404, description: 'Trip not found (`NOT_FOUND`).', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 409, description: 'The trip is not ongoing (`TRIP_STATUS_TRANSITION_INVALID`).', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ],
    )]
    public function complete(Request $request, string $trip): JsonResponse
    {
        $model = Trip::findOrFail($trip);
        $this->authorize('complete', $model);

        $completed = $this->trips->complete($model);

        return ApiResponse::success(new TripResource($completed->loadCount('passengers')), 'Trip completed.');
    }

    /**
     * Cancel a trip the user drives: scheduled or ongoing → cancelled.
     */
    #[OA\Post(
        path: '/api/v1/trips/{trip}/cancel',
        operationId: 'cancelTrip',
        summary: 'Cancel a trip',
        tags: ['Trips'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'trip', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Trip cancelled', content: new OA\JsonContent(ref: '#/components/schemas/TripResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated (`UNAUTHENTICATED`).', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Forbidden, only the driver may cancel the trip (`UNAUTHORIZED`).', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 404, description: 'Trip not found (`NOT_FOUND`).', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 409, description: 'The trip has already finished (`TRIP_STATUS_TRANSITION_INVALID`).', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ],
    )]
    public function cancel(Request $request, string $trip): JsonResponse
    {
        $model = Trip::findOrFail($trip);
        $this->authorize('cancel', $model);

        $cancelled = $this->trips->cancel($model);

        return ApiResponse::success(new TripResource($cancelled->loadCount('passengers')), 'Trip cancelled.');
    }
}

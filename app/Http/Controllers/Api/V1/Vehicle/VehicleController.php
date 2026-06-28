<?php

namespace App\Http\Controllers\Api\V1\Vehicle;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Vehicle\StoreVehicleRequest;
use App\Http\Requests\Api\V1\Vehicle\UpdateVehicleRequest;
use App\Http\Resources\V1\VehicleResource;
use App\Models\Vehicle;
use App\Services\Vehicle\VehicleService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class VehicleController extends Controller
{
    public function __construct(private VehicleService $vehicles) {}

    /**
     * List the authenticated user's vehicles.
     */
    #[OA\Get(
        path: '/api/v1/vehicles',
        operationId: 'listVehicles',
        summary: 'List the authenticated user\'s vehicles',
        tags: ['Vehicles'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'List of vehicles', content: new OA\JsonContent(ref: '#/components/schemas/VehicleCollectionResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ],
    )]
    public function index(Request $request): JsonResponse
    {
        $vehicles = $request->user()->vehicles()->latest()->get();

        return ApiResponse::success(VehicleResource::collection($vehicles), 'Vehicles retrieved.');
    }

    /**
     * Attach a new vehicle to the authenticated user.
     */
    #[OA\Post(
        path: '/api/v1/vehicles',
        operationId: 'createVehicle',
        summary: 'Attach a new vehicle',
        tags: ['Vehicles'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StoreVehicleInput'),
        ),
        responses: [
            new OA\Response(response: 201, description: 'Vehicle created', content: new OA\JsonContent(ref: '#/components/schemas/VehicleResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ],
    )]
    public function store(StoreVehicleRequest $request): JsonResponse
    {
        $vehicle = $this->vehicles->create($request->user(), $request->validated());

        return ApiResponse::success(new VehicleResource($vehicle), 'Vehicle created.', 201);
    }

    /**
     * Show one of the authenticated user's vehicles.
     */
    #[OA\Get(
        path: '/api/v1/vehicles/{vehicle}',
        operationId: 'showVehicle',
        summary: 'Show a vehicle',
        tags: ['Vehicles'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'vehicle', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Vehicle details', content: new OA\JsonContent(ref: '#/components/schemas/VehicleResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 404, description: 'Vehicle not found', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ],
    )]
    public function show(Request $request, string $vehicle): JsonResponse
    {
        return ApiResponse::success(new VehicleResource($this->resolveVehicle($request, $vehicle)), 'Vehicle retrieved.');
    }

    /**
     * Update one of the authenticated user's vehicles.
     */
    #[OA\Put(
        path: '/api/v1/vehicles/{vehicle}',
        operationId: 'updateVehicle',
        summary: 'Update a vehicle',
        tags: ['Vehicles'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'vehicle', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UpdateVehicleInput'),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Vehicle updated', content: new OA\JsonContent(ref: '#/components/schemas/VehicleResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 404, description: 'Vehicle not found', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ],
    )]
    public function update(UpdateVehicleRequest $request, string $vehicle): JsonResponse
    {
        $updated = $this->vehicles->update($this->resolveVehicle($request, $vehicle), $request->validated());

        return ApiResponse::success(new VehicleResource($updated), 'Vehicle updated.');
    }

    /**
     * Detach (delete) one of the authenticated user's vehicles.
     */
    #[OA\Delete(
        path: '/api/v1/vehicles/{vehicle}',
        operationId: 'deleteVehicle',
        summary: 'Delete a vehicle',
        tags: ['Vehicles'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'vehicle', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Vehicle deleted', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess')),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 404, description: 'Vehicle not found', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ],
    )]
    public function destroy(Request $request, string $vehicle): JsonResponse
    {
        $this->vehicles->delete($this->resolveVehicle($request, $vehicle));

        return ApiResponse::success(null, 'Vehicle deleted.');
    }

    /**
     * Resolve a vehicle scoped to the authenticated user, 404ing on anything
     * that does not belong to them so ownership is never leaked.
     */
    private function resolveVehicle(Request $request, string $vehicleId): Vehicle
    {
        return $request->user()->vehicles()->findOrFail($vehicleId);
    }
}

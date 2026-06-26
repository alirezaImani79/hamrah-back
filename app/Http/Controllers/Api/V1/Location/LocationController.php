<?php

namespace App\Http\Controllers\Api\V1\Location;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\CityResource;
use App\Http\Resources\V1\ProvinceResource;
use App\Models\Province;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class LocationController extends Controller
{
    /**
     * List every active Iranian province for address selection.
     */
    #[OA\Get(
        path: '/api/v1/locations/provinces',
        operationId: 'listProvinces',
        summary: 'List Iranian provinces',
        description: 'Public reference list of all active provinces, ordered by name. Use the returned id when submitting an address.',
        tags: ['Locations'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Provinces retrieved',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: '#/components/schemas/ApiSuccess'),
                        new OA\Schema(properties: [
                            new OA\Property(
                                property: 'data',
                                type: 'array',
                                items: new OA\Items(ref: '#/components/schemas/Province'),
                            ),
                        ]),
                    ],
                ),
            ),
        ],
    )]
    public function provinces(): JsonResponse
    {
        $provinces = Province::active()->orderBy('name')->get();

        return ApiResponse::success(ProvinceResource::collection($provinces), 'Provinces retrieved.');
    }

    /**
     * List the active cities that belong to a given province.
     */
    #[OA\Get(
        path: '/api/v1/locations/provinces/{province}/cities',
        operationId: 'listProvinceCities',
        summary: 'List the cities of a province',
        description: 'Public reference list of the active cities within a province, ordered by name.',
        tags: ['Locations'],
        parameters: [
            new OA\Parameter(
                name: 'province',
                in: 'path',
                required: true,
                description: 'Province id (from the provinces list).',
                schema: new OA\Schema(type: 'integer', example: 8),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Cities retrieved',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: '#/components/schemas/ApiSuccess'),
                        new OA\Schema(properties: [
                            new OA\Property(
                                property: 'data',
                                type: 'array',
                                items: new OA\Items(ref: '#/components/schemas/City'),
                            ),
                        ]),
                    ],
                ),
            ),
            new OA\Response(response: 404, description: 'Province not found', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ],
    )]
    public function cities(Province $province): JsonResponse
    {
        $cities = $province->cities()->active()->orderBy('name')->get();

        return ApiResponse::success(CityResource::collection($cities), 'Cities retrieved.');
    }
}

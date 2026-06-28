<?php

namespace App\Http\Resources\V1;

use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * @mixin Trip
 */
#[OA\Schema(
    schema: 'Trip',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'vehicle_id', type: 'integer', example: 1),
        new OA\Property(
            property: 'origin',
            type: 'object',
            properties: [
                new OA\Property(property: 'lat', type: 'number', format: 'float', example: 35.6892),
                new OA\Property(property: 'lng', type: 'number', format: 'float', example: 51.3890),
            ],
        ),
        new OA\Property(
            property: 'destination',
            type: 'object',
            properties: [
                new OA\Property(property: 'lat', type: 'number', format: 'float', example: 32.6539),
                new OA\Property(property: 'lng', type: 'number', format: 'float', example: 51.6660),
            ],
        ),
        new OA\Property(property: 'departure_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'empty_seats', type: 'integer', example: 3),
        new OA\Property(property: 'trunk_empty', type: 'boolean', example: true),
        new OA\Property(property: 'role', type: 'string', enum: ['driver', 'passenger'], example: 'driver'),
        new OA\Property(property: 'passengers_count', type: 'integer', example: 2),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
)]
class TripResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'vehicle_id' => $this->vehicle_id,
            'origin' => [
                'lat' => (float) $this->origin_lat,
                'lng' => (float) $this->origin_lng,
            ],
            'destination' => [
                'lat' => (float) $this->destination_lat,
                'lng' => (float) $this->destination_lng,
            ],
            'departure_at' => $this->departure_at,
            'empty_seats' => $this->empty_seats,
            'trunk_empty' => $this->trunk_empty,
            'role' => $request->user()?->getKey() === $this->user_id ? 'driver' : 'passenger',
            'passengers_count' => $this->whenCounted('passengers'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

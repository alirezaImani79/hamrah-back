<?php

namespace App\Http\Resources\V1;

use App\Models\Trip;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * A trip returned by the match endpoint, enriched with how well it fits the
 * passenger's request.
 *
 * @mixin Trip
 */
#[OA\Schema(
    schema: 'MatchedTrip',
    title: 'Matched trip',
    allOf: [new OA\Schema(ref: '#/components/schemas/Trip')],
    properties: [
        new OA\Property(property: 'available_seats', type: 'integer', description: 'Empty seats not yet taken by other passengers.', example: 2),
        new OA\Property(property: 'origin_distance_km', type: 'number', format: 'float', description: 'Distance between the trip and passenger origins.', example: 0.55),
        new OA\Property(property: 'destination_distance_km', type: 'number', format: 'float', description: 'Distance between the trip and passenger destinations.', example: 1.1),
    ],
)]
class MatchedTripResource extends TripResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'available_seats' => $this->available_seats,
            'origin_distance_km' => $this->origin_distance_km,
            'destination_distance_km' => $this->destination_distance_km,
        ]);
    }
}

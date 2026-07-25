<?php

namespace App\Http\Resources\V1;

use App\Models\Trip;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * A trip enriched with its driver, vehicle, and signed-up passengers.
 *
 * Used by the trip detail page shown to the driver and passengers.
 *
 * @mixin Trip
 */
#[OA\Schema(
    schema: 'TripDetail',
    title: 'Trip detail',
    allOf: [new OA\Schema(ref: '#/components/schemas/Trip')],
    properties: [
        new OA\Property(property: 'driver', ref: '#/components/schemas/TripParticipant', nullable: true),
        new OA\Property(property: 'vehicle', ref: '#/components/schemas/Vehicle', nullable: true),
        new OA\Property(
            property: 'passengers',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/TripParticipant'),
        ),
    ],
)]
class TripDetailResource extends TripResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'driver' => new TripParticipantResource($this->whenLoaded('user')),
            'vehicle' => new VehicleResource($this->whenLoaded('vehicle')),
            'passengers' => TripParticipantResource::collection($this->whenLoaded('passengers')),
            'passengers_count' => $this->resource->relationLoaded('passengers')
                ? $this->resource->passengers->count()
                : $this->whenCounted('passengers'),
        ]);
    }
}

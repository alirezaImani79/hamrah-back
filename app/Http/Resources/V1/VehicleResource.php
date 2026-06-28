<?php

namespace App\Http\Resources\V1;

use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * @mixin Vehicle
 */
#[OA\Schema(
    schema: 'Vehicle',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'number', type: 'string', description: 'License plate of the vehicle.', example: '12 ج 345 67'),
        new OA\Property(property: 'name', type: 'string', description: 'A preferred name chosen by the user.', example: 'Daily driver'),
        new OA\Property(property: 'seats', type: 'integer', example: 4),
        new OA\Property(property: 'color', type: 'string', example: 'White'),
        new OA\Property(property: 'model', type: 'string', description: 'The vehicle model name.', example: 'Peugeot 206'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
)]
class VehicleResource extends JsonResource
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
            'number' => $this->number,
            'name' => $this->name,
            'seats' => $this->seats,
            'color' => $this->color,
            'model' => $this->model,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

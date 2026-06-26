<?php

namespace App\Http\Resources\V1;

use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * @mixin City
 */
#[OA\Schema(
    schema: 'City',
    title: 'City',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 760),
        new OA\Property(property: 'name', type: 'string', example: 'تهران'),
        new OA\Property(property: 'province_id', type: 'integer', example: 8),
    ],
)]
class CityResource extends JsonResource
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
            'name' => $this->name,
            'province_id' => $this->province_id,
        ];
    }
}

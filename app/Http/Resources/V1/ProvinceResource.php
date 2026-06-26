<?php

namespace App\Http\Resources\V1;

use App\Models\Province;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * @mixin Province
 */
#[OA\Schema(
    schema: 'Province',
    title: 'Province',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 8),
        new OA\Property(property: 'name', type: 'string', example: 'تهران'),
        new OA\Property(property: 'code', type: 'string', example: '23'),
    ],
)]
class ProvinceResource extends JsonResource
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
            'code' => $this->code,
        ];
    }
}

<?php

namespace App\Http\Requests\Api\V1\Trip;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UpdateTripInput',
    description: 'All fields are optional; only the provided fields are updated.',
    properties: [
        new OA\Property(property: 'vehicle_id', type: 'integer', description: 'A vehicle belonging to the authenticated user.', example: 1),
        new OA\Property(property: 'origin_lat', type: 'number', format: 'float', minimum: -90, maximum: 90, example: 35.6892),
        new OA\Property(property: 'origin_lng', type: 'number', format: 'float', minimum: -180, maximum: 180, example: 51.3890),
        new OA\Property(property: 'destination_lat', type: 'number', format: 'float', minimum: -90, maximum: 90, example: 32.6539),
        new OA\Property(property: 'destination_lng', type: 'number', format: 'float', minimum: -180, maximum: 180, example: 51.6660),
        new OA\Property(property: 'departure_at', type: 'string', format: 'date-time', description: 'When the trip departs; must be in the future.', example: '2026-07-01 08:30:00'),
        new OA\Property(property: 'empty_seats', type: 'integer', minimum: 1, maximum: 100, example: 3),
        new OA\Property(property: 'trunk_empty', type: 'boolean', description: 'Whether the vehicle trunk is empty for luggage.', example: true),
    ],
)]
class UpdateTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'vehicle_id' => [
                'sometimes',
                'required',
                'integer',
                // The vehicle must belong to the authenticated driver.
                Rule::exists('vehicles', 'id')->where('user_id', $this->user()->getKey()),
            ],
            'origin_lat' => ['sometimes', 'required', 'numeric', 'between:-90,90'],
            'origin_lng' => ['sometimes', 'required', 'numeric', 'between:-180,180'],
            'destination_lat' => ['sometimes', 'required', 'numeric', 'between:-90,90'],
            'destination_lng' => ['sometimes', 'required', 'numeric', 'between:-180,180'],
            'departure_at' => ['sometimes', 'required', 'date', 'after:now'],
            'empty_seats' => ['sometimes', 'required', 'integer', 'min:1', 'max:100'],
            'trunk_empty' => ['sometimes', 'required', 'boolean'],
        ];
    }
}

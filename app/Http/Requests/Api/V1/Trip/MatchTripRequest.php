<?php

namespace App\Http\Requests\Api\V1\Trip;

use App\Enums\Gender;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'MatchTripInput',
    required: ['origin_lat', 'origin_lng', 'destination_lat', 'destination_lng', 'departure_at', 'requested_seats'],
    properties: [
        new OA\Property(property: 'origin_lat', type: 'number', format: 'float', minimum: -90, maximum: 90, description: 'The passenger\'s pickup latitude.', example: 35.6892),
        new OA\Property(property: 'origin_lng', type: 'number', format: 'float', minimum: -180, maximum: 180, description: 'The passenger\'s pickup longitude.', example: 51.3890),
        new OA\Property(property: 'destination_lat', type: 'number', format: 'float', minimum: -90, maximum: 90, description: 'The passenger\'s dropoff latitude.', example: 32.6539),
        new OA\Property(property: 'destination_lng', type: 'number', format: 'float', minimum: -180, maximum: 180, description: 'The passenger\'s dropoff longitude.', example: 51.6660),
        new OA\Property(property: 'departure_at', type: 'string', format: 'date-time', description: 'The passenger\'s preferred travel time; must be in the future.', example: '2026-07-15 08:30:00'),
        new OA\Property(property: 'requested_seats', type: 'integer', minimum: 1, maximum: 4, description: 'How many seats the passenger needs.', example: 2),
        new OA\Property(property: 'driver_gender', type: 'string', enum: ['male', 'female'], nullable: true, description: 'Optional restriction on the driver\'s gender. Omit for no preference.', example: 'female'),
    ],
)]
class MatchTripRequest extends FormRequest
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
            'origin_lat' => ['required', 'numeric', 'between:-90,90'],
            'origin_lng' => ['required', 'numeric', 'between:-180,180'],
            'destination_lat' => ['required', 'numeric', 'between:-90,90'],
            'destination_lng' => ['required', 'numeric', 'between:-180,180'],
            'departure_at' => ['required', 'date', 'after:now'],
            'requested_seats' => ['required', 'integer', 'min:1', 'max:4'],
            'driver_gender' => ['nullable', Rule::enum(Gender::class)],
        ];
    }
}

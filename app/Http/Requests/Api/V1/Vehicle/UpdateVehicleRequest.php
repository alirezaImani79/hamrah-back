<?php

namespace App\Http\Requests\Api\V1\Vehicle;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UpdateVehicleInput',
    description: 'All fields are optional; only the provided fields are updated.',
    properties: [
        new OA\Property(property: 'number', type: 'string', description: 'License plate of the vehicle.', example: '12 ج 345 67'),
        new OA\Property(property: 'name', type: 'string', description: 'A preferred name chosen by the user.', example: 'Daily driver'),
        new OA\Property(property: 'seats', type: 'integer', minimum: 1, maximum: 100, example: 4),
        new OA\Property(property: 'color', type: 'string', example: 'White'),
        new OA\Property(property: 'model', type: 'string', description: 'The vehicle model name.', example: 'Peugeot 206'),
    ],
)]
class UpdateVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Trim the license plate so duplicate detection is not bypassed by padding.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('number')) {
            $this->merge([
                'number' => trim((string) $this->input('number')),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'number' => [
                'sometimes',
                'required',
                'string',
                'max:32',
                // The same user cannot register the same license plate twice.
                Rule::unique('vehicles', 'number')
                    ->where('user_id', $this->user()->getKey())
                    ->ignore($this->route('vehicle')),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'seats' => ['sometimes', 'required', 'integer', 'min:1', 'max:100'],
            'color' => ['sometimes', 'required', 'string', 'max:50'],
            'model' => ['sometimes', 'required', 'string', 'max:255'],
        ];
    }
}

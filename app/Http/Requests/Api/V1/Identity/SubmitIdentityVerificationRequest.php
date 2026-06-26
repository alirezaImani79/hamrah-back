<?php

namespace App\Http\Requests\Api\V1\Identity;

use App\Enums\Gender;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SubmitIdentityVerificationInput',
    required: ['first_name', 'last_name', 'national_code', 'birth_date', 'gender', 'province_id', 'city_id', 'address', 'national_card_image', 'face_image'],
    properties: [
        new OA\Property(property: 'first_name', type: 'string', example: 'Ali'),
        new OA\Property(property: 'last_name', type: 'string', example: 'Imani'),
        new OA\Property(property: 'national_code', type: 'string', example: '0012345678'),
        new OA\Property(property: 'birth_date', type: 'string', format: 'date', example: '1990-05-21'),
        new OA\Property(property: 'gender', type: 'string', enum: ['male', 'female'], example: 'male'),
        new OA\Property(property: 'province_id', type: 'integer', description: 'Province id from GET /api/v1/locations/provinces.', example: 8),
        new OA\Property(property: 'city_id', type: 'integer', description: 'City id from GET /api/v1/locations/provinces/{province}/cities; must belong to province_id.', example: 760),
        new OA\Property(property: 'address', type: 'string', description: 'Free-text address details (street, alley, no., postal code).', example: 'خیابان ولیعصر، کوچه ۵، پلاک ۱۲'),
        new OA\Property(property: 'national_card_image', type: 'string', format: 'binary', description: 'Photo of the national ID card.'),
        new OA\Property(property: 'face_image', type: 'string', format: 'binary', description: 'Selfie of the user.'),
    ],
)]
class SubmitIdentityVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize the national code: map Persian/Arabic digits to ASCII and
     * strip any non-digit characters before validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('national_code')) {
            $this->merge([
                'national_code' => $this->normalizeDigits((string) $this->input('national_code')),
            ]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $maxImageKb = (int) config('identity.max_image_kilobytes');

        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'national_code' => [
                'required',
                'string',
                'regex:/^[0-9]{10}$/',
                Rule::unique('users', 'national_code')->ignore($this->user()->getKey()),
            ],
            'birth_date' => ['required', 'date', 'before:today'],
            'gender' => ['required', Rule::enum(Gender::class)],
            'province_id' => ['required', 'integer', Rule::exists('iran_provinces', 'id')],
            'city_id' => [
                'required',
                'integer',
                // The city must exist *and* belong to the selected province.
                Rule::exists('iran_cities', 'id')->where('province_id', $this->input('province_id')),
            ],
            'address' => ['required', 'string', 'max:1000'],
            'national_card_image' => ['required', 'image', 'mimes:jpeg,jpg,png', 'max:'.$maxImageKb],
            'face_image' => ['required', 'image', 'mimes:jpeg,jpg,png', 'max:'.$maxImageKb],
        ];
    }

    /**
     * Convert Persian/Arabic numerals to ASCII and remove all non-digits.
     */
    private function normalizeDigits(string $value): string
    {
        $value = strtr($value, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        return (string) preg_replace('/[^0-9]/', '', $value);
    }
}

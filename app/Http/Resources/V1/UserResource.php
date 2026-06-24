<?php

namespace App\Http\Resources\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * @mixin User
 */
#[OA\Schema(
    schema: 'User',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'phone_number', type: 'string', example: '+15551234567'),
        new OA\Property(property: 'name', type: 'string', nullable: true, example: 'Jane Doe'),
        new OA\Property(property: 'email', type: 'string', nullable: true, example: 'jane@example.com'),
        new OA\Property(property: 'phone_verified_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'is_subscribed_to_newsletter', type: 'boolean', example: false),
        new OA\Property(property: 'newsletter_subscribed_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'first_name', type: 'string', nullable: true, example: 'Ali'),
        new OA\Property(property: 'last_name', type: 'string', nullable: true, example: 'Imani'),
        new OA\Property(property: 'national_code', type: 'string', nullable: true, example: '0012345678'),
        new OA\Property(property: 'birth_date', type: 'string', format: 'date', nullable: true, example: '1990-05-21'),
        new OA\Property(property: 'gender', type: 'string', enum: ['male', 'female'], nullable: true, example: 'male'),
        new OA\Property(property: 'identity_status', type: 'string', enum: ['pending', 'verifying', 'verified', 'rejected'], example: 'verifying'),
        new OA\Property(property: 'is_identity_verified', type: 'boolean', example: false),
        new OA\Property(property: 'identity_verified_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'identity_rejection_reason', type: 'string', nullable: true, example: 'The selfie does not match the card photo.'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
)]
class UserResource extends JsonResource
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
            'phone_number' => $this->phone_number,
            'name' => $this->name,
            'email' => $this->email,
            'phone_verified_at' => $this->phone_verified_at,
            'is_subscribed_to_newsletter' => $this->isSubscribedToNewsletter(),
            'newsletter_subscribed_at' => $this->newsletter_subscribed_at,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'national_code' => $this->national_code,
            'birth_date' => $this->birth_date?->toDateString(),
            'gender' => $this->gender?->value,
            'identity_status' => $this->identity_status?->value,
            'is_identity_verified' => $this->isIdentityVerified(),
            'identity_verified_at' => $this->identity_verified_at,
            'identity_rejection_reason' => $this->identityRejectionReason(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

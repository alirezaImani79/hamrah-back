<?php

namespace App\Http\Resources\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * A user as seen by others on a shared trip (driver or passenger).
 *
 * Exposes only what fellow riders need to identify and contact each other —
 * never the sensitive own-profile fields (national code, address, identity docs).
 *
 * @mixin User
 */
#[OA\Schema(
    schema: 'TripParticipant',
    title: 'Trip participant',
    description: 'A user as seen by others on a shared trip (driver or passenger).',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'first_name', type: 'string', nullable: true, example: 'Ali'),
        new OA\Property(property: 'last_name', type: 'string', nullable: true, example: 'Imani'),
        new OA\Property(property: 'gender', type: 'string', enum: ['male', 'female'], nullable: true, example: 'male'),
        new OA\Property(property: 'phone_number', type: 'string', example: '+15551234567'),
        new OA\Property(property: 'is_identity_verified', type: 'boolean', example: false),
    ],
)]
class TripParticipantResource extends JsonResource
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
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'gender' => $this->gender?->value,
            'phone_number' => $this->phone_number,
            'is_identity_verified' => $this->isIdentityVerified(),
        ];
    }
}

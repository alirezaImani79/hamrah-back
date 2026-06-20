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
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

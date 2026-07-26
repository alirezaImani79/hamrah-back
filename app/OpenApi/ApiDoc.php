<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Hamrah API',
    description: 'REST API for the Hamrah application. Every response uses a uniform JSON envelope: '
        .'`{ success, message, data }` on success and `{ success, message, code, errors }` on failure, '
        .'where `code` is a stable machine-readable error code the client localizes against.',
)]
#[OA\Server(url: L5_SWAGGER_CONST_HOST, description: 'API server')]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Token',
    description: 'Pass the token returned by /api/v1/auth/otp/verify as: Authorization: Bearer {token}',
)]
#[OA\Schema(
    schema: 'ApiSuccess',
    title: 'Success envelope',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', example: 'OK'),
        new OA\Property(property: 'data', type: 'object', nullable: true),
    ],
)]
#[OA\Schema(
    schema: 'ApiError',
    title: 'Error envelope',
    description: 'Every error response shares this shape. `code` is the stable, machine-readable '
        .'identifier the client should use to resolve a localized message; `message` is an English '
        .'developer/human fallback and may change. Codes returned per endpoint are listed in that '
        ."endpoint's response descriptions. Legend: "
        .'`VALIDATION_FAILED` (422, request body failed rules; per-field detail under `errors`), '
        .'`UNAUTHENTICATED` (401), `UNAUTHORIZED` (403, policy denied), '
        .'`NOT_FOUND` (404, modeled resource missing), `ENDPOINT_NOT_FOUND` (404, unknown route), '
        .'`METHOD_NOT_ALLOWED` (405), `TOO_MANY_REQUESTS` (429, throttled), `SERVER_ERROR` (500), '
        .'`OTP_INVALID` (422, code invalid/expired/used up), '
        .'`OTP_REQUEST_THROTTLED` (422, code requested too soon), '
        .'`IDENTITY_VERIFICATION_IN_PROGRESS` (409), `IDENTITY_ALREADY_VERIFIED` (409), '
        .'`TRIP_FULL` (422), `TRIP_ALREADY_JOINED` (422), '
        .'`TRIP_STATUS_TRANSITION_INVALID` (409, illegal lifecycle transition).',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
        new OA\Property(
            property: 'code',
            type: 'string',
            description: 'Stable machine-readable error code. Use this — not `message` — to pick a localized string.',
            enum: [
                'VALIDATION_FAILED',
                'UNAUTHENTICATED',
                'UNAUTHORIZED',
                'NOT_FOUND',
                'ENDPOINT_NOT_FOUND',
                'METHOD_NOT_ALLOWED',
                'TOO_MANY_REQUESTS',
                'SERVER_ERROR',
                'OTP_INVALID',
                'OTP_REQUEST_THROTTLED',
                'IDENTITY_VERIFICATION_IN_PROGRESS',
                'IDENTITY_ALREADY_VERIFIED',
                'TRIP_FULL',
                'TRIP_ALREADY_JOINED',
                'TRIP_STATUS_TRANSITION_INVALID',
            ],
            example: 'VALIDATION_FAILED',
        ),
        new OA\Property(
            property: 'errors',
            type: 'object',
            nullable: true,
            example: ['phone_number' => ['The phone number field is required.']],
        ),
    ],
)]
#[OA\Schema(
    schema: 'UserResponse',
    title: 'User envelope',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string'),
        new OA\Property(property: 'data', ref: '#/components/schemas/User'),
    ],
)]
#[OA\Schema(
    schema: 'AuthTokenResponse',
    title: 'Authentication envelope',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string'),
        new OA\Property(
            property: 'data',
            type: 'object',
            properties: [
                new OA\Property(property: 'token', type: 'string', example: '1|AbCdEf0123456789...'),
                new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
                new OA\Property(
                    property: 'is_new_user',
                    type: 'boolean',
                    description: 'True when this OTP verification registered a brand-new user, signalling the client to show the identity completion flow.',
                    example: false,
                ),
                new OA\Property(property: 'user', ref: '#/components/schemas/User'),
            ],
        ),
    ],
)]
#[OA\Schema(
    schema: 'VehicleResponse',
    title: 'Vehicle envelope',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string'),
        new OA\Property(property: 'data', ref: '#/components/schemas/Vehicle'),
    ],
)]
#[OA\Schema(
    schema: 'VehicleCollectionResponse',
    title: 'Vehicle collection envelope',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string'),
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/Vehicle'),
        ),
    ],
)]
#[OA\Schema(
    schema: 'TripResponse',
    title: 'Trip envelope',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string'),
        new OA\Property(property: 'data', ref: '#/components/schemas/Trip'),
    ],
)]
#[OA\Schema(
    schema: 'TripDetailResponse',
    title: 'Trip detail envelope',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string'),
        new OA\Property(property: 'data', ref: '#/components/schemas/TripDetail'),
    ],
)]
#[OA\Schema(
    schema: 'TripCollectionResponse',
    title: 'Trip collection envelope',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string'),
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/Trip'),
        ),
    ],
)]
#[OA\Schema(
    schema: 'MatchedTripCollectionResponse',
    title: 'Matched trip collection envelope',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string'),
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/MatchedTrip'),
        ),
    ],
)]
class ApiDoc
{
    //
}

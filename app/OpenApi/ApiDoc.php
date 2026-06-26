<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Hamrah API',
    description: 'REST API for the Hamrah application. Every response uses a uniform JSON envelope: '
        .'`{ success, message, data }` on success and `{ success, message, errors }` on failure.',
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
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
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
class ApiDoc
{
    //
}

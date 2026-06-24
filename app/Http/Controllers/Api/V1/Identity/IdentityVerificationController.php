<?php

namespace App\Http\Controllers\Api\V1\Identity;

use App\Enums\IdentityVerificationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Identity\SubmitIdentityVerificationRequest;
use App\Http\Resources\V1\UserResource;
use App\Services\Identity\IdentityVerificationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class IdentityVerificationController extends Controller
{
    public function __construct(private IdentityVerificationService $identity) {}

    /**
     * Show the authenticated user's identity verification status.
     */
    #[OA\Get(
        path: '/api/v1/identity',
        operationId: 'identityStatus',
        summary: 'Get the identity verification status',
        tags: ['Identity'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Current status', content: new OA\JsonContent(ref: '#/components/schemas/UserResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ],
    )]
    public function status(Request $request): JsonResponse
    {
        return ApiResponse::success(new UserResource($request->user()), 'Identity status retrieved.');
    }

    /**
     * Submit identity data and documents, then queue the automated review.
     */
    #[OA\Post(
        path: '/api/v1/identity/verify',
        operationId: 'submitIdentityVerification',
        summary: 'Submit identity information for verification',
        description: 'Stores the user details and documents, marks the account as "verifying", '
            .'and dispatches a background job that compares the data with a vision model.',
        tags: ['Identity'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(ref: '#/components/schemas/SubmitIdentityVerificationInput'),
            ),
        ),
        responses: [
            new OA\Response(response: 202, description: 'Submitted for verification', content: new OA\JsonContent(ref: '#/components/schemas/UserResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 409, description: 'Verification already in progress or completed', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ],
    )]
    public function submit(SubmitIdentityVerificationRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user->identity_status === IdentityVerificationStatus::Verifying) {
            abort(409, 'Your identity verification is already in progress.');
        }

        if ($user->identity_status === IdentityVerificationStatus::Verified) {
            abort(409, 'Your identity has already been verified.');
        }

        $user = $this->identity->submit(
            $user,
            $request->safe()->only(['first_name', 'last_name', 'national_code', 'birth_date', 'gender']),
            $request->file('national_card_image'),
            $request->file('face_image'),
        );

        return ApiResponse::success(new UserResource($user), 'Identity submitted for verification.', 202);
    }
}

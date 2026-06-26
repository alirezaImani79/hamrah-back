<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\RequestOtpRequest;
use App\Http\Requests\Api\V1\Auth\VerifyOtpRequest;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use App\Services\Auth\OtpService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    /**
     * Request an OTP code for a phone number.
     */
    #[OA\Post(
        path: '/api/v1/auth/otp/request',
        operationId: 'requestOtp',
        summary: 'Request an OTP code',
        description: 'Generates a one-time code and sends it to the phone number via SMS.',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/RequestOtpInput'),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Code sent', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 429, description: 'Too many requests', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ],
    )]
    public function requestOtp(RequestOtpRequest $request, OtpService $otp): JsonResponse
    {
        $code = $otp->request($request->validated('phone_number'));

        // The plain code is only ever exposed in non-production environments.
        $data = app()->environment(['local', 'testing'])
            ? ['debug_code' => $code]
            : null;

        return ApiResponse::success($data, 'Verification code sent.');
    }

    /**
     * Verify an OTP code and issue a long-lived access token.
     */
    #[OA\Post(
        path: '/api/v1/auth/otp/verify',
        operationId: 'verifyOtp',
        summary: 'Verify an OTP code',
        description: 'Verifies the code, creates the user if needed, and returns a bearer token.',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/VerifyOtpInput'),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Authenticated', content: new OA\JsonContent(ref: '#/components/schemas/AuthTokenResponse')),
            new OA\Response(response: 422, description: 'Invalid or expired code', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ],
    )]
    public function verifyOtp(VerifyOtpRequest $request, OtpService $otp): JsonResponse
    {
        $phoneNumber = $request->validated('phone_number');

        if (! $otp->verify($phoneNumber, $request->validated('code'))) {
            throw ValidationException::withMessages([
                'code' => ['The provided code is invalid or has expired.'],
            ]);
        }

        $user = User::firstOrCreate(['phone_number' => $phoneNumber]);

        // A freshly created record means this is the user's first authentication,
        // so the client should prompt them to complete identity verification.
        $isNewUser = $user->wasRecentlyCreated;

        if ($user->phone_verified_at === null) {
            $user->forceFill(['phone_verified_at' => now()])->save();
        }

        return ApiResponse::success([
            'token' => $user->createToken('mobile-app')->plainTextToken,
            'token_type' => 'Bearer',
            'is_new_user' => $isNewUser,
            'user' => new UserResource($user),
        ], 'Authenticated successfully.');
    }

    /**
     * Get the currently authenticated user.
     */
    #[OA\Get(
        path: '/api/v1/auth/me',
        operationId: 'me',
        summary: 'Get the authenticated user',
        tags: ['Auth'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Current user', content: new OA\JsonContent(ref: '#/components/schemas/UserResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ],
    )]
    public function me(Request $request): JsonResponse
    {
        return ApiResponse::success(new UserResource($request->user()), 'Authenticated user retrieved.');
    }

    /**
     * Revoke the access token used for the current request.
     */
    #[OA\Post(
        path: '/api/v1/auth/logout',
        operationId: 'logout',
        summary: 'Log out (revoke current token)',
        tags: ['Auth'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Logged out', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess')),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ],
    )]
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return ApiResponse::success(null, 'Logged out successfully.');
    }
}

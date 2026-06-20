<?php

namespace App\Http\Controllers\Api\V1\Newsletter;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\UserResource;
use App\Services\Newsletter\NewsletterService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class NewsletterController extends Controller
{
    public function __construct(private NewsletterService $newsletter) {}

    /**
     * Show the authenticated user's newsletter subscription status.
     */
    #[OA\Get(
        path: '/api/v1/newsletter',
        operationId: 'newsletterStatus',
        summary: 'Get the newsletter subscription status',
        tags: ['Newsletter'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Current status', content: new OA\JsonContent(ref: '#/components/schemas/UserResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ],
    )]
    public function status(Request $request): JsonResponse
    {
        return ApiResponse::success(new UserResource($request->user()), 'Newsletter status retrieved.');
    }

    /**
     * Subscribe the authenticated user to the SMS newsletter.
     */
    #[OA\Post(
        path: '/api/v1/newsletter/subscribe',
        operationId: 'subscribeNewsletter',
        summary: 'Subscribe to the SMS newsletter',
        tags: ['Newsletter'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Subscribed', content: new OA\JsonContent(ref: '#/components/schemas/UserResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ],
    )]
    public function subscribe(Request $request): JsonResponse
    {
        $user = $this->newsletter->subscribe($request->user());

        return ApiResponse::success(new UserResource($user), 'Subscribed to the newsletter.');
    }

    /**
     * Unsubscribe the authenticated user from the SMS newsletter.
     */
    #[OA\Post(
        path: '/api/v1/newsletter/unsubscribe',
        operationId: 'unsubscribeNewsletter',
        summary: 'Unsubscribe from the SMS newsletter',
        tags: ['Newsletter'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Unsubscribed', content: new OA\JsonContent(ref: '#/components/schemas/UserResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ],
    )]
    public function unsubscribe(Request $request): JsonResponse
    {
        $user = $this->newsletter->unsubscribe($request->user());

        return ApiResponse::success(new UserResource($user), 'Unsubscribed from the newsletter.');
    }
}

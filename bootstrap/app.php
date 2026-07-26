<?php

use App\Exceptions\ApiException;
use App\Http\Middleware\ForceJsonResponse;
use App\Support\ApiResponse;
use App\Support\ErrorCode;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            ForceJsonResponse::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // Every API exception is rendered through the same JSON envelope.
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return match (true) {
                $e instanceof ApiException => ApiResponse::error(
                    $e->getMessage() ?: 'Error.',
                    $e->errors,
                    $e->getStatusCode(),
                    $e->errorCode,
                ),
                $e instanceof ValidationException => ApiResponse::error(
                    'The given data was invalid.',
                    $e->errors(),
                    422,
                    ErrorCode::ValidationFailed,
                ),
                $e instanceof AuthenticationException => ApiResponse::error('Unauthenticated.', null, 401, ErrorCode::Unauthenticated),
                $e instanceof AuthorizationException,
                $e instanceof AccessDeniedHttpException => ApiResponse::error(
                    $e->getMessage() ?: 'This action is unauthorized.',
                    null,
                    403,
                    ErrorCode::Unauthorized,
                ),
                $e instanceof ModelNotFoundException => ApiResponse::error('Resource not found.', null, 404, ErrorCode::NotFound),
                $e instanceof NotFoundHttpException => ApiResponse::error('The requested endpoint was not found.', null, 404, ErrorCode::EndpointNotFound),
                $e instanceof MethodNotAllowedHttpException => ApiResponse::error('The HTTP method is not allowed for this endpoint.', null, 405, ErrorCode::MethodNotAllowed),
                $e instanceof TooManyRequestsHttpException => ApiResponse::error('Too many requests. Please slow down.', null, 429, ErrorCode::TooManyRequests),
                $e instanceof HttpExceptionInterface => ApiResponse::error(
                    $e->getMessage() ?: 'HTTP error.',
                    null,
                    $e->getStatusCode(),
                    ErrorCode::ServerError,
                ),
                default => ApiResponse::error(
                    config('app.debug') ? $e->getMessage() : 'Server error.',
                    config('app.debug') ? ['exception' => $e::class] : null,
                    500,
                    ErrorCode::ServerError,
                ),
            };
        });
    })->create();

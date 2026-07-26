<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    /**
     * Build a successful JSON envelope.
     */
    public static function success(mixed $data = null, string $message = 'OK', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    /**
     * Build a failed JSON envelope.
     *
     * The `code` is a stable, machine-readable identifier (see {@see ErrorCode})
     * the frontend uses to resolve a localized message; `message` remains an
     * English developer/human fallback.
     *
     * @param  array<string, mixed>|null  $errors
     */
    public static function error(string $message, ?array $errors = null, int $status = 400, ?ErrorCode $code = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'code' => ($code ?? ErrorCode::ServerError)->value,
            'errors' => $errors,
        ], $status);
    }
}

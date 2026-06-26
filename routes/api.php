<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Identity\IdentityVerificationController;
use App\Http\Controllers\Api\V1\Location\LocationController;
use App\Http\Controllers\Api\V1\Newsletter\NewsletterController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('otp/request', [AuthController::class, 'requestOtp'])->middleware('throttle:6,1');
        Route::post('otp/verify', [AuthController::class, 'verifyOtp'])->middleware('throttle:6,1');

        Route::middleware('auth:sanctum')->group(function () {
            Route::get('me', [AuthController::class, 'me']);
            Route::post('logout', [AuthController::class, 'logout']);
        });
    });

    // Public reference data for address selection (provinces & their cities).
    Route::prefix('locations')->group(function () {
        Route::get('provinces', [LocationController::class, 'provinces']);
        Route::get('provinces/{province}/cities', [LocationController::class, 'cities']);
    });

    Route::middleware('auth:sanctum')->prefix('newsletter')->group(function () {
        Route::get('/', [NewsletterController::class, 'status']);
        Route::post('subscribe', [NewsletterController::class, 'subscribe']);
        Route::post('unsubscribe', [NewsletterController::class, 'unsubscribe']);
    });

    Route::middleware('auth:sanctum')->prefix('identity')->group(function () {
        Route::get('/', [IdentityVerificationController::class, 'status']);
        Route::post('verify', [IdentityVerificationController::class, 'submit']);
    });
});

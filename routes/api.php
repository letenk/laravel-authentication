<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HealthController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [HealthController::class, 'index']);

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::middleware('auth:api')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);

        Route::prefix('/email')->group(function(){
            Route::post('/send-otp', [AuthController::class, 'sendEmailOtp']);
            Route::post('/verify', [AuthController::class, 'verifyEmail']);
        });
    });
});

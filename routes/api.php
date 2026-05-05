<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [HealthController::class, 'index']);

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('/refresh', [AuthController::class, 'refresh'])->middleware('throttle:refresh');
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:forgot-password');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:reset-password');

    Route::middleware('auth:api')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::delete('/me', [AuthController::class, 'deleteAccount']);
        Route::post('/logout-all', [AuthController::class, 'logoutAll']);

        Route::prefix('/email')->group(function () {
            Route::post('/send-otp', [AuthController::class, 'sendEmailOtp'])->middleware('throttle:email-send-otp');
            Route::post('/verify', [AuthController::class, 'verifyEmail'])->middleware('throttle:email-verify');
        });
    });
});

Route::prefix('user')->middleware('auth:api')->group(function () {
    Route::get('/sessions', [UserController::class, 'sessions']);
    Route::delete('/sessions/{id}', [UserController::class, 'revokeSession']);
    Route::put('/profile', [UserController::class, 'updateProfile']);
    Route::put('/password', [UserController::class, 'changePassword']);
});

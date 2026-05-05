<?php

namespace App\Support;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class BootRateLimiter
{
    public static function boot(): void
    {
        static::registerLimiters();
    }

    private static function registerLimiters(): void
    {
        RateLimiter::for('register', fn (Request $request) =>
            Limit::perMinute(5)->by($request->ip())
        );

        RateLimiter::for('login', fn (Request $request) =>
            Limit::perMinute(10)->by($request->ip())
        );

        RateLimiter::for('refresh', fn (Request $request) =>
            Limit::perMinute(20)->by($request->ip())
        );

        RateLimiter::for('forgot-password', fn (Request $request) =>
            Limit::perMinute(3)->by($request->ip())
        );

        RateLimiter::for('reset-password', fn (Request $request) =>
            Limit::perMinute(5)->by($request->ip())
        );

        RateLimiter::for('email-send-otp', fn (Request $request) =>
            Limit::perMinute(3)->by($request->user()?->id ?: $request->ip())
        );

        RateLimiter::for('email-verify', fn (Request $request) =>
            Limit::perMinute(10)->by($request->user()?->id ?: $request->ip())
        );
    }
}

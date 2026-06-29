<?php

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;

trait ManagesAuthCookies
{
    protected function withAuthCookies(JsonResponse $response, string $accessToken, string $refreshToken): JsonResponse
    {
        $secure     = app()->environment('production');
        $accessTtl  = (int) config('jwt.ttl');
        $refreshTtl = (int) env('REFRESH_TOKEN_TTL_DAYS', 7) * 24 * 60;

        return $response
            ->withCookie(cookie('access_token', $accessToken, $accessTtl, '/', null, $secure, true, false, 'Lax'))
            ->withCookie(cookie('refresh_token', $refreshToken, $refreshTtl, '/', null, $secure, true, false, 'Lax'));
    }

    protected function withoutAuthCookies(JsonResponse $response): JsonResponse
    {
        return $response
            ->withCookie(cookie()->forget('access_token', '/'))
            ->withCookie(cookie()->forget('refresh_token', '/'));
    }
}

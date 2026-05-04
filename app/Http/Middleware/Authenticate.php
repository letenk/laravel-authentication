<?php

namespace App\Http\Middleware;

use App\Models\User;
use Illuminate\Auth\Middleware\Authenticate as BaseAuthenticate;
use Illuminate\Http\Request;

class Authenticate extends BaseAuthenticate
{
    protected function authenticate($request, array $guards): void
    {
        if (config('auth.bypass', false) && !app()->environment('production')) {
            $user = User::first();

            if ($user) {
                $this->auth->guard('api')->setUser($user);
                $this->auth->shouldUse('api');
                return;
            }
        }

        parent::authenticate($request, $guards);
    }

    protected function redirectTo(Request $request): ?string
    {
        return null;
    }
}

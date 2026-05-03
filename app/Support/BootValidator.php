<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

class BootValidator
{
    public static function boot(): void
    {
        static::registerPasswordRules();
    }

    private static function registerPasswordRules(): void
    {
        Password::defaults(function () {
            return Password::min(8)
                ->mixedCase()
                ->numbers()
                ->symbols();
        });
    }
}

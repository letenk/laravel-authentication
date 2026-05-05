<?php

namespace App\Http\Requests\Auth;

use Illuminate\Validation\Rules\Password;
use Spatie\LaravelData\Data;

class ResetPasswordRequest extends Data
{
    public function __construct(
        public readonly string $email,
        public readonly string $code,
        public readonly string $password,
    ) {}

    public static function rules(): array
    {
        return [
            'email'    => ['required', 'email'],
            'code'     => ['required', 'string', 'digits:' . config('otp.length')],
            'password' => ['required', 'string', Password::defaults()],
        ];
    }
}

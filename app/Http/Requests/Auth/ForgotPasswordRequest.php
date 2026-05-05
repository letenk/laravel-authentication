<?php

namespace App\Http\Requests\Auth;

use Spatie\LaravelData\Data;

class ForgotPasswordRequest extends Data
{
    public function __construct(
        public readonly string $email,
    ) {}

    public static function rules(): array
    {
        return [
            'email' => ['required', 'email'],
        ];
    }
}

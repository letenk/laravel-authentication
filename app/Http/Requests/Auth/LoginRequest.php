<?php

namespace App\Http\Requests\Auth;

use Spatie\LaravelData\Data;

class LoginRequest extends Data
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
    ) {}

    public static function rules(): array
    {
        return [
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }
}

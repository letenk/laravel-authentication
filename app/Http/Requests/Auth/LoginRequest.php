<?php

namespace App\Http\Requests\Auth;

use Spatie\LaravelData\Data;

class LoginRequest extends Data
{
    public function __construct(
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly string $password,
    ) {}

    public static function rules(): array
    {
        return [
            'email'    => ['nullable', 'email', 'required_without:phone'],
            'phone'    => ['nullable', 'string', 'regex:/^\+\d{1,3}\d+$/', 'required_without:email'],
            'password' => ['required', 'string'],
        ];
    }
}

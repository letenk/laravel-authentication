<?php

namespace App\Http\Requests\Auth;

use Illuminate\Validation\Rules\Password;
use Spatie\LaravelData\Data;

class RegisterRequest extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly string $password,
    ) {}

    public static function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['nullable', 'email', 'max:255', 'unique:users,email', 'required_without:phone'],
            'phone'    => ['nullable', 'string', 'max:20', 'regex:/^\+\d{1,3}\d+$/', 'required_without:email'],
            // uniqueness checked in service after normalization
            'password' => ['required', 'string', Password::defaults()],
        ];
    }
}

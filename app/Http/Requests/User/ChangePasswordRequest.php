<?php

namespace App\Http\Requests\User;

use Illuminate\Validation\Rules\Password;
use Spatie\LaravelData\Data;

class ChangePasswordRequest extends Data
{
    public function __construct(
        public readonly string $current_password,
        public readonly string $password,
    ) {}

    public static function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'string', Password::defaults()],
        ];
    }
}

<?php

namespace App\Http\Requests\Auth;

use App\Rules\UniqueNormalizedPhone;
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
            'phone'    => ['bail', 'nullable', 'phone', 'required_without:email', new UniqueNormalizedPhone()],
            'password' => ['required', 'string', Password::defaults()],
        ];
    }
}

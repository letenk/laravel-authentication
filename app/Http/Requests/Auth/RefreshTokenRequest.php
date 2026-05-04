<?php

namespace App\Http\Requests\Auth;

use Spatie\LaravelData\Data;

class RefreshTokenRequest extends Data
{
    public function __construct(
        public readonly string $refresh_token,
    ) {}

    public static function rules(): array
    {
        return [
            'refresh_token' => ['required', 'string'],
        ];
    }
}

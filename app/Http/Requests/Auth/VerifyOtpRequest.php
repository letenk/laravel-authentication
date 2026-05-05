<?php

namespace App\Http\Requests\Auth;

use Spatie\LaravelData\Data;

class VerifyOtpRequest extends Data
{
    public function __construct(
        public readonly string $code,
    ) {}

    public static function rules(): array
    {
        return [
            'code' => ['required', 'string', 'digits:' . config('otp.length')],
        ];
    }
}

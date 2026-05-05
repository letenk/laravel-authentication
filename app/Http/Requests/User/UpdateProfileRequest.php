<?php

namespace App\Http\Requests\User;

use App\Rules\UniqueNormalizedPhone;
use Spatie\LaravelData\Data;

class UpdateProfileRequest extends Data
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $phone = null,
    ) {}

    public static function rules(): array
    {
        $userId = request()->user()?->id;

        return [
            'name'  => ['sometimes', 'nullable', 'string', 'max:255'],
            'phone' => ['bail', 'sometimes', 'nullable', 'phone', new UniqueNormalizedPhone($userId)],
        ];
    }
}

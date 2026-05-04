<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueNormalizedPhone implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $normalized = ltrim(phone($value)->formatE164(), '+');

        if (User::where('phone', $normalized)->exists()) {
            $fail('The :attribute has already been taken.');
        }
    }
}

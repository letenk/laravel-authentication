<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueNormalizedPhone implements ValidationRule
{
    public function __construct(private readonly ?int $ignoreUserId = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $normalized = ltrim(phone($value)->formatE164(), '+');

        $query = User::where('phone', $normalized);

        if ($this->ignoreUserId) {
            $query->where('id', '!=', $this->ignoreUserId);
        }

        if ($query->exists()) {
            $fail('The :attribute has already been taken.');
        }
    }
}

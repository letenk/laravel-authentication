<?php

namespace App\Models\Scope;

use Illuminate\Database\Eloquent\Builder;

class UserScope extends Builder
{
    public function filterById(int $id): static
    {
        return $this->where('id', $id);
    }

    public function filterByEmail(string $email): static
    {
        return $this->where('email', $email);
    }

    public function filterByPhone(string $phone): static
    {
        return $this->where('phone', $phone);
    }
}

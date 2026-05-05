<?php

namespace App\Models\Scope;

use Illuminate\Database\Eloquent\Builder;

class RefreshTokenScope extends Builder
{
    public function filterById(int $id): static
    {
        return $this->where('id', $id);
    }

    public function filterByToken(string $token): static
    {
        return $this->where('token', $token);
    }

    public function filterByUserId(int $userId): static
    {
        return $this->where('user_id', $userId);
    }

    public function onlyActive(): static
    {
        return $this->whereNull('revoked_at')
            ->where('expires_at', '>', now());
    }
}

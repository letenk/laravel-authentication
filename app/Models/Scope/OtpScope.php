<?php

namespace App\Models\Scope;

use Illuminate\Database\Eloquent\Builder;

class OtpScope extends Builder
{
    public function filterByUserId(int $userId): static
    {
        return $this->where('user_id', $userId);
    }

    public function filterByChannel(string $channel): static
    {
        return $this->where('channel', $channel);
    }

    public function filterByPurpose(string $purpose): static
    {
        return $this->where('purpose', $purpose);
    }

    public function pending(): static
    {
        return $this->whereNull('verified_at')
            ->where('expires_at', '>', now());
    }
}

<?php

namespace App\Repository;

use App\DTOs\RefreshToken\RefreshTokenRepositoryDTO;
use App\Models\RefreshToken;
use App\Models\Scope\RefreshTokenScope;
use Illuminate\Database\Eloquent\Collection;

class RefreshTokenRepository
{
    private function baseQuery(array $select = ['*'], array $relations = []): RefreshTokenScope
    {
        $query = RefreshToken::query()->select($select);

        if (!empty($relations)) {
            $query->with($relations);
        }

        return $query;
    }

    public function applyFilters(RefreshTokenScope $query, array $filters): RefreshTokenScope
    {
        if (isset($filters['id'])) {
            $query->filterById($filters['id']);
        }

        if (isset($filters['token'])) {
            $query->filterByToken($filters['token']);
        }

        if (isset($filters['user_id'])) {
            $query->filterByUserId($filters['user_id']);
        }

        if (isset($filters['only_active']) && $filters['only_active']) {
            $query->onlyActive();
        }

        return $query;
    }

    public function first(RefreshTokenRepositoryDTO $dto): ?RefreshToken
    {
        $query = $this->baseQuery($dto->select, $dto->eagerLoadRelation);
        $query = $this->applyFilters($query, $dto->filters);

        return $query->first();
    }

    public function get(RefreshTokenRepositoryDTO $dto): Collection
    {
        $query = $this->baseQuery($dto->select, $dto->eagerLoadRelation);
        $query = $this->applyFilters($query, $dto->filters);

        return $query->latest()->get();
    }

    public function create(array $data): RefreshToken
    {
        return RefreshToken::create($data);
    }

    public function revokeAndReplace(RefreshToken $old, string $newToken): void
    {
        $old->revoked_at         = now();
        $old->replaced_by_token  = $newToken;
        $old->save();
    }

    public function revoke(RefreshToken $token): void
    {
        $token->revoked_at = now();
        $token->save();
    }

    public function revokeAllByUserId(int $userId): void
    {
        RefreshToken::query()
            ->filterByUserId($userId)
            ->onlyActive()
            ->update(['revoked_at' => now()]);
    }
}

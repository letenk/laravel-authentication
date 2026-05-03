<?php

namespace App\Repository;

use App\DTOs\User\UserRepositoryDTO;
use App\Models\Scope\UserScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class UserRepository
{
    private function baseQuery(array $select = ['*'], array $relations = []): UserScope
    {
        $query = User::query()->select($select);

        if (!empty($relations)) {
            $query->with($relations);
        }

        return $query;
    }

    public function applyFilters(UserScope $query, array $filters): UserScope
    {
        if (isset($filters['id'])) {
            $query->filterById($filters['id']);
        }

        if (isset($filters['email'])) {
            $query->filterByEmail($filters['email']);
        }

        if (isset($filters['phone'])) {
            $query->filterByPhone($filters['phone']);
        }

        return $query;
    }

    public function first(UserRepositoryDTO $dto): ?User
    {
        $query = $this->baseQuery($dto->select, $dto->eagerLoadRelation);
        $query = $this->applyFilters($query, $dto->filters);

        return $query->first();
    }

    public function get(UserRepositoryDTO $dto): Collection
    {
        $query = $this->baseQuery($dto->select, $dto->eagerLoadRelation);
        $query = $this->applyFilters($query, $dto->filters);

        return $query->get();
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function save(User $user): User
    {
        $user->save();
        return $user;
    }
}

<?php

namespace App\Repository;

use App\DTOs\Otp\OtpRepositoryDTO;
use App\Models\Otp;
use App\Models\Scope\OtpScope;

class OtpRepository
{
    private function baseQuery(array $select = ['*'], array $relations = []): OtpScope
    {
        $query = Otp::query()->select($select);

        if (!empty($relations)) {
            $query->with($relations);
        }

        return $query;
    }

    public function applyFilters(OtpScope $query, array $filters): OtpScope
    {
        if (isset($filters['user_id'])) {
            $query->filterByUserId($filters['user_id']);
        }

        if (isset($filters['channel'])) {
            $query->filterByChannel($filters['channel']);
        }

        if (isset($filters['purpose'])) {
            $query->filterByPurpose($filters['purpose']);
        }

        if (!empty($filters['pending'])) {
            $query->pending();
        }

        return $query;
    }

    public function first(OtpRepositoryDTO $dto): ?Otp
    {
        $query = $this->baseQuery($dto->select, $dto->eagerLoadRelation);
        $query = $this->applyFilters($query, $dto->filters);

        return $query->latest()->first();
    }

    public function create(array $data): Otp
    {
        return Otp::create($data);
    }

    public function expireAll(int $userId, string $channel, string $purpose): void
    {
        Otp::query()
            ->filterByUserId($userId)
            ->filterByChannel($channel)
            ->filterByPurpose($purpose)
            ->pending()
            ->update(['expires_at' => now()]);
    }

    public function markVerified(Otp $otp): void
    {
        $otp->verified_at = now();
        $otp->save();
    }

    public function incrementSubmitAttempt(Otp $otp): void
    {
        $otp->increment('submit_attempt');
    }
}

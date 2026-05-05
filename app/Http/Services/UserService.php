<?php

namespace App\Http\Services;

use App\DTOs\RefreshToken\RefreshTokenRepositoryDTO;
use App\Exceptions\GeneralException;
use App\Models\RefreshToken;
use App\Models\User;
use App\Repository\RefreshTokenRepository;
use Illuminate\Database\Eloquent\Collection;

class UserService
{
    protected RefreshTokenRepository $refreshTokenRepository;

    public function __construct(RefreshTokenRepository $refreshTokenRepository)
    {
        $this->refreshTokenRepository = $refreshTokenRepository;
    }

    public function getSessions(User $user): Collection
    {
        return $this->refreshTokenRepository->get(new RefreshTokenRepositoryDTO([
            'select'  => ['id', 'device_name', 'device_id', 'ip_address', 'user_agent', 'created_at', 'expires_at'],
            'filters' => ['user_id' => $user->id, 'only_active' => true],
        ]));
    }

    public function revokeSession(User $user, int $sessionId): void
    {
        $session = $this->refreshTokenRepository->first(new RefreshTokenRepositoryDTO([
            'filters' => ['id' => $sessionId, 'user_id' => $user->id, 'only_active' => true],
        ]));

        if (!$session) {
            throw GeneralException::create('Session not found.', null, 404);
        }

        $this->refreshTokenRepository->revoke($session);
    }
}

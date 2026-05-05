<?php

namespace App\Http\Services;

use App\DTOs\RefreshToken\RefreshTokenRepositoryDTO;
use App\Exceptions\GeneralException;
use App\Http\Requests\User\ChangePasswordRequest;
use App\Http\Requests\User\UpdateProfileRequest;
use App\Models\User;
use App\Repository\RefreshTokenRepository;
use App\Repository\UserRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;

class UserService
{
    protected RefreshTokenRepository $refreshTokenRepository;
    protected UserRepository $userRepository;

    public function __construct(
        RefreshTokenRepository $refreshTokenRepository,
        UserRepository $userRepository,
    ) {
        $this->refreshTokenRepository = $refreshTokenRepository;
        $this->userRepository         = $userRepository;
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

    public function updateProfile(User $user, UpdateProfileRequest $dto): User
    {
        if ($dto->name !== null) {
            $user->name = $dto->name;
        }

        if ($dto->phone !== null) {
            $user->phone = ltrim(phone($dto->phone)->formatE164(), '+');
        }

        return $this->userRepository->save($user);
    }

    public function changePassword(User $user, ChangePasswordRequest $dto): void
    {
        if (!Hash::check($dto->current_password, $user->password)) {
            throw GeneralException::create('Current password is incorrect.', null, 422);
        }

        $user->password = Hash::make($dto->password);
        $this->userRepository->save($user);

        $this->refreshTokenRepository->revokeAllByUserId($user->id);
    }
}

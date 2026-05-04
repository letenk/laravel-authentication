<?php

namespace App\Http\Services;

use App\DTOs\RefreshToken\RefreshTokenRepositoryDTO;
use App\DTOs\User\UserRepositoryDTO;
use App\Exceptions\GeneralException;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RefreshTokenRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Repository\RefreshTokenRepository;
use App\Repository\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthService
{
    protected UserRepository $userRepository;
    protected RefreshTokenRepository $refreshTokenRepository;

    public function __construct(
        UserRepository $userRepository,
        RefreshTokenRepository $refreshTokenRepository,
    ) {
        $this->userRepository        = $userRepository;
        $this->refreshTokenRepository = $refreshTokenRepository;
    }

    public function register(RegisterRequest $dto): User
    {
        return $this->userRepository->create([
            'name'       => $dto->name,
            'email'      => $dto->email,
            'password'   => $dto->password,
            'login_type' => 'email',
        ]);
    }

    public function login(LoginRequest $dto, Request $request): array
    {
        $user = $this->userRepository->first(new UserRepositoryDTO([
            'filters' => ['email' => $dto->email],
        ]));

        if (!$user || !Hash::check($dto->password, $user->password)) {
            throw GeneralException::create('Invalid credentials.', null, 401);
        }

        $accessToken  = JWTAuth::fromUser($user);
        $refreshToken = Str::uuid()->toString();

        $this->refreshTokenRepository->create([
            'user_id'     => $user->id,
            'token'       => $refreshToken,
            'device_name' => $request->input('device_name'),
            'device_id'   => $request->input('device_id'),
            'ip_address'  => $request->ip(),
            'user_agent'  => $request->userAgent(),
            'expires_at'  => now()->addDays((int) env('REFRESH_TOKEN_TTL_DAYS', 7)),
        ]);

        return [
            'token'         => $accessToken,
            'token_type'    => 'Bearer',
            'expires_in'    => config('jwt.ttl') * 60,
            'refresh_token' => $refreshToken,
        ];
    }

    public function refresh(RefreshTokenRequest $dto): array
    {
        $old = $this->refreshTokenRepository->first(new RefreshTokenRepositoryDTO([
            'filters' => ['token' => $dto->refresh_token, 'only_active' => true],
        ]));

        if (!$old) {
            throw GeneralException::create('Invalid or expired refresh token.', null, 401);
        }

        $user = $old->user;

        return DB::transaction(function () use ($old, $user) {
            $newRefreshToken = Str::uuid()->toString();
            $newAccessToken  = JWTAuth::fromUser($user);

            $this->refreshTokenRepository->revokeAndReplace($old, $newRefreshToken);

            $this->refreshTokenRepository->create([
                'user_id'     => $user->id,
                'token'       => $newRefreshToken,
                'device_name' => $old->device_name,
                'device_id'   => $old->device_id,
                'ip_address'  => $old->ip_address,
                'user_agent'  => $old->user_agent,
                'expires_at'  => now()->addDays((int) env('REFRESH_TOKEN_TTL_DAYS', 7)),
            ]);

            return [
                'token'         => $newAccessToken,
                'token_type'    => 'Bearer',
                'expires_in'    => config('jwt.ttl') * 60,
                'refresh_token' => $newRefreshToken,
            ];
        });
    }

    public function logout(RefreshTokenRequest $dto): void
    {
        $token = $this->refreshTokenRepository->first(new RefreshTokenRepositoryDTO([
            'filters' => ['token' => $dto->refresh_token, 'only_active' => true],
        ]));

        if (!$token) {
            throw GeneralException::create('Invalid or expired refresh token.', null, 401);
        }

        $this->refreshTokenRepository->revoke($token);
    }
}

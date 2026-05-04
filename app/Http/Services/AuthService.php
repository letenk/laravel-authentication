<?php

namespace App\Http\Services;

use App\DTOs\User\UserRepositoryDTO;
use App\Exceptions\GeneralException;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Repository\UserRepository;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthService
{
    protected UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
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

    public function login(LoginRequest $dto): array
    {
        $user = $this->userRepository->first(new UserRepositoryDTO([
            'filters' => ['email' => $dto->email],
        ]));

        if (!$user || !Hash::check($dto->password, $user->password)) {
            throw GeneralException::create('Invalid credentials.', null, 401);
        }

        $token = JWTAuth::fromUser($user);

        return [
            'token'      => $token,
            'token_type' => 'Bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ];
    }

    public function logout(): void
    {
        JWTAuth::invalidate(JWTAuth::getToken());
    }
}

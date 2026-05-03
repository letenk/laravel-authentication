<?php

namespace App\Http\Services;

use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Repository\UserRepository;

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
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RefreshTokenRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends BaseController
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(RegisterRequest $dto): JsonResponse
    {
        $user = $this->authService->register($dto);

        return $this->successResponse('Registration successful.', $user, 201);
    }

    public function login(LoginRequest $dto, Request $request): JsonResponse
    {
        $result = $this->authService->login($dto, $request);

        return $this->successResponse('Login successful.', $result);
    }

    public function me(Request $request): JsonResponse
    {
        return $this->successResponse('User retrieved.', $request->user());
    }

    public function refresh(RefreshTokenRequest $dto): JsonResponse
    {
        $result = $this->authService->refresh($dto);

        return $this->successResponse('Token refreshed.', $result);
    }

    public function logout(RefreshTokenRequest $dto): JsonResponse
    {
        $this->authService->logout($dto);

        return $this->successResponse('Logged out successfully.');
    }
}

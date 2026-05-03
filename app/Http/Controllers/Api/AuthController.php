<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Services\AuthService;
use Illuminate\Http\JsonResponse;

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
}

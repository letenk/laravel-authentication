<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\User\ChangePasswordRequest;
use App\Http\Requests\User\UpdateProfileRequest;
use App\Http\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends BaseController
{
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function sessions(Request $request): JsonResponse
    {
        $sessions = $this->userService->getSessions($request->user());

        return $this->successResponse('Sessions retrieved.', $sessions);
    }

    public function revokeSession(Request $request, int $id): JsonResponse
    {
        $this->userService->revokeSession($request->user(), $id);

        return $this->successResponse('Session revoked.');
    }

    public function updateProfile(UpdateProfileRequest $dto, Request $request): JsonResponse
    {
        $user = $this->userService->updateProfile($request->user(), $dto);

        return $this->successResponse('Profile updated.', $user);
    }

    public function changePassword(ChangePasswordRequest $dto, Request $request): JsonResponse
    {
        $this->userService->changePassword($request->user(), $dto);

        return $this->successResponse('Password changed. Please login again.');
    }
}

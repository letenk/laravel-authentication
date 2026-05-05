<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RefreshTokenRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Services\AuthService;
use App\Http\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends BaseController
{
    protected AuthService $authService;
    protected OtpService $otpService;

    public function __construct(
        AuthService $authService,
        OtpService $otpService,
    ) {
        $this->authService = $authService;
        $this->otpService  = $otpService;
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

    public function forgotPassword(ForgotPasswordRequest $dto): JsonResponse
    {
        $this->authService->forgotPassword($dto);

        return $this->successResponse('If that email is registered, a reset code has been sent.');
    }

    public function resetPassword(ResetPasswordRequest $dto): JsonResponse
    {
        $this->authService->resetPassword($dto);

        return $this->successResponse('Password reset successfully.');
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $this->authService->logoutAll($request->user());

        return $this->successResponse('Logged out from all devices.');
    }

    public function deleteAccount(Request $request): JsonResponse
    {
        $this->authService->deleteAccount($request->user());

        return $this->successResponse('Account deleted.');
    }

    public function sendEmailOtp(Request $request): JsonResponse
    {
        $this->otpService->send($request->user(), 'email', 'email_verification');

        return $this->successResponse('Verification code sent to your email.');
    }

    public function verifyEmail(VerifyOtpRequest $dto, Request $request): JsonResponse
    {
        $this->otpService->verify($request->user(), $dto->code, 'email', 'email_verification');

        return $this->successResponse('Email verified successfully.');
    }
}

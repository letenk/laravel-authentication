<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\GeneralException;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RefreshTokenRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Services\AuthService;
use App\Http\Services\OtpService;
use App\Http\Traits\ManagesAuthCookies;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends BaseController
{
    use ManagesAuthCookies;

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

        return $this->withAuthCookies(
            $this->successResponse('Login successful.', $result),
            $result['token'],
            $result['refresh_token'],
        );
    }

    public function me(Request $request): JsonResponse
    {
        return $this->successResponse('User retrieved.', $request->user());
    }

    public function refresh(Request $request): JsonResponse
    {
        $refreshToken = $request->input('refresh_token') ?? $request->cookie('refresh_token');

        if (!$refreshToken) {
            throw GeneralException::create('Refresh token is required.', null, 401);
        }

        $dto    = RefreshTokenRequest::from(['refresh_token' => $refreshToken]);
        $result = $this->authService->refresh($dto);

        return $this->withAuthCookies(
            $this->successResponse('Token refreshed.', $result),
            $result['token'],
            $result['refresh_token'],
        );
    }

    public function logout(Request $request): JsonResponse
    {
        $refreshToken = $request->input('refresh_token') ?? $request->cookie('refresh_token');

        if (!$refreshToken) {
            throw GeneralException::create('Refresh token is required.', null, 401);
        }

        $dto = RefreshTokenRequest::from(['refresh_token' => $refreshToken]);
        $this->authService->logout($dto);

        return $this->withoutAuthCookies($this->successResponse('Logged out successfully.'));
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $this->authService->logoutAll($request->user());

        return $this->withoutAuthCookies($this->successResponse('Logged out from all devices.'));
    }

    public function deleteAccount(Request $request): JsonResponse
    {
        $this->authService->deleteAccount($request->user());

        return $this->successResponse('Account deleted.');
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

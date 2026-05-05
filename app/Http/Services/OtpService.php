<?php

namespace App\Http\Services;

use App\DTOs\Otp\OtpRepositoryDTO;
use App\Exceptions\GeneralException;
use App\Mail\OtpMail;
use App\Models\Otp;
use App\Models\User;
use App\Repository\OtpRepository;
use App\Repository\UserRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OtpService
{
    protected OtpRepository $otpRepository;
    protected UserRepository $userRepository;

    public function __construct(
        OtpRepository $otpRepository,
        UserRepository $userRepository,
    ) {
        $this->otpRepository  = $otpRepository;
        $this->userRepository = $userRepository;
    }

    public function send(User $user, string $channel, string $purpose): Otp
    {
        if ($purpose === 'email_verification' && $user->is_verified) {
            throw GeneralException::create('Email is already verified.', null, 422);
        }

        $length      = config('otp.length');
        $ttl         = config('otp.ttl_minutes');
        $waitMinutes = config('otp.next_attempt_wait_minutes');

        $existing = $this->otpRepository->first(new OtpRepositoryDTO([
            'filters' => [
                'user_id' => $user->id,
                'channel' => $channel,
                'purpose' => $purpose,
                'pending' => true,
            ],
        ]));

        if ($existing && $existing->next_attempt_at && $existing->next_attempt_at->isFuture()) {
            throw GeneralException::create('Please wait before requesting a new code.', [
                'next_attempt_at' => $existing->next_attempt_at,
            ], 429);
        }

        $this->otpRepository->expireAll($user->id, $channel, $purpose);

        $max  = (int) str_repeat('9', $length);
        $code = str_pad((string) random_int(0, $max), $length, '0', STR_PAD_LEFT);

        $otp = $this->otpRepository->create([
            'user_id'         => $user->id,
            'channel'         => $channel,
            'purpose'         => $purpose,
            'code'            => $code,
            'expires_at'      => now()->addMinutes($ttl),
            'attempt'         => ($existing->attempt ?? 0) + 1,
            'next_attempt_at' => now()->addMinutes($waitMinutes),
        ]);

        if ($channel === 'email') {
            $this->sendOtpEmail($user, $otp);
        }

        return $otp;
    }

    public function verify(User $user, string $code, string $channel, string $purpose): void
    {
        $otp = $this->otpRepository->first(new OtpRepositoryDTO([
            'filters' => [
                'user_id' => $user->id,
                'channel' => $channel,
                'purpose' => $purpose,
                'pending' => true,
            ],
        ]));

        if (!$otp) {
            throw GeneralException::create('No active code found. Please request a new one.', null, 422);
        }

        if ($otp->submit_attempt >= config('otp.max_submit_attempt')) {
            throw GeneralException::create('Too many attempts. Please request a new code.', null, 422);
        }

        if ($otp->code !== $code) {
            $this->otpRepository->incrementSubmitAttempt($otp);
            throw GeneralException::create('Invalid code.', null, 422);
        }

        $this->otpRepository->markVerified($otp);

        if ($purpose === 'email_verification') {
            $user->is_verified = true;
            $user->verified_at = now();
            $user->save();
        }
    }

    private function sendOtpEmail(User $user, Otp $otp): void
    {
        try {
            Mail::to($user->email)->queue(new OtpMail($user, $otp));
        } catch (\Throwable $e) {
            Log::error('Failed to queue OTP email', [
                'user_id' => $user->id,
                'email'   => $user->email,
                'error'   => $e->getMessage(),
            ]);
            throw GeneralException::create('Failed to send verification code. Please try again.', null, 500);
        }
    }
}

<?php

namespace Tests\Feature\Auth;

use App\Models\Otp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_with_email_queues_otp(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/v1/auth/register', [
            'name'     => 'Test User',
            'email'    => 'user@example.com',
            'password' => 'NewSecurePass456!',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('otps', [
            'channel' => 'email',
            'purpose' => 'email_verification',
        ]);

        Mail::assertQueued(\App\Mail\OtpMail::class);
    }

    public function test_register_with_phone_does_not_queue_email_otp(): void
    {
        Mail::fake();

        $this->postJson('/api/v1/auth/register', [
            'name'     => 'Test User',
            'phone'    => '+628123456789',
            'password' => 'NewSecurePass456!',
        ]);

        Mail::assertNothingQueued();
    }

    public function test_send_email_otp_success(): void
    {
        Mail::fake();

        $user  = User::factory()->create(['email' => 'user@example.com']);
        $token = $this->loginAndGetToken($user);

        $response = $this->withToken($token)->postJson('/api/v1/auth/email/send-otp');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');

        Mail::assertQueued(\App\Mail\OtpMail::class);
    }

    public function test_send_email_otp_rejected_if_already_verified(): void
    {
        Mail::fake();

        $user  = User::factory()->verified()->create(['email' => 'user@example.com']);
        $token = $this->loginAndGetToken($user);

        $response = $this->withToken($token)->postJson('/api/v1/auth/email/send-otp');

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error');

        Mail::assertNothingQueued();
    }

    public function test_send_email_otp_rate_limited(): void
    {
        Mail::fake();

        $user  = User::factory()->create(['email' => 'user@example.com']);
        $token = $this->loginAndGetToken($user);

        $this->withToken($token)->postJson('/api/v1/auth/email/send-otp');

        $response = $this->withToken($token)->postJson('/api/v1/auth/email/send-otp');

        $response->assertStatus(429)
            ->assertJsonPath('status', 'error');
    }

    public function test_verify_email_success(): void
    {
        Mail::fake();

        $user  = User::factory()->create(['email' => 'user@example.com']);
        $token = $this->loginAndGetToken($user);

        Otp::create([
            'user_id'    => $user->id,
            'channel'    => 'email',
            'purpose'    => 'email_verification',
            'code'       => '12345',
            'expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->withToken($token)->postJson('/api/v1/auth/email/verify', [
            'code' => '12345',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('users', [
            'id'          => $user->id,
            'is_verified' => true,
        ]);

        $this->assertDatabaseHas('otps', [
            'user_id' => $user->id,
            'code'    => '12345',
        ]);

        $this->assertNotNull(Otp::where('user_id', $user->id)->value('verified_at'));
    }

    public function test_verify_email_fails_with_wrong_code(): void
    {
        $user  = User::factory()->create(['email' => 'user@example.com']);
        $token = $this->loginAndGetToken($user);

        Otp::create([
            'user_id'    => $user->id,
            'channel'    => 'email',
            'purpose'    => 'email_verification',
            'code'       => '12345',
            'expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->withToken($token)->postJson('/api/v1/auth/email/verify', [
            'code' => '99999',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error');
    }

    public function test_verify_email_fails_with_expired_otp(): void
    {
        $user  = User::factory()->create(['email' => 'user@example.com']);
        $token = $this->loginAndGetToken($user);

        Otp::create([
            'user_id'    => $user->id,
            'channel'    => 'email',
            'purpose'    => 'email_verification',
            'code'       => '12345',
            'expires_at' => now()->subMinute(),
        ]);

        $response = $this->withToken($token)->postJson('/api/v1/auth/email/verify', [
            'code' => '12345',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error');
    }

    public function test_verify_email_locked_after_max_attempts(): void
    {
        $user  = User::factory()->create(['email' => 'user@example.com']);
        $token = $this->loginAndGetToken($user);

        Otp::create([
            'user_id'        => $user->id,
            'channel'        => 'email',
            'purpose'        => 'email_verification',
            'code'           => '12345',
            'expires_at'     => now()->addMinutes(10),
            'submit_attempt' => 5,
        ]);

        $response = $this->withToken($token)->postJson('/api/v1/auth/email/verify', [
            'code' => '12345',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error');
    }

    public function test_verify_email_requires_auth(): void
    {
        $response = $this->postJson('/api/v1/auth/email/verify', [
            'code' => '12345',
        ]);

        $response->assertStatus(401);
    }

    private function loginAndGetToken(User $user): string
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => $user->email,
            'password' => 'password',
        ]);

        return $response->json('data.token');
    }
}

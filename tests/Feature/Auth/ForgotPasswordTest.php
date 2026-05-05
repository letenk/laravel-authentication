<?php

namespace Tests\Feature\Auth;

use App\Models\Otp;
use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_sends_otp_to_registered_email(): void
    {
        Mail::fake();

        User::factory()->create(['email' => 'user@example.com']);

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'user@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');

        Mail::assertQueued(\App\Mail\OtpMail::class);
    }

    public function test_forgot_password_returns_200_for_unregistered_email(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'nobody@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');

        Mail::assertNothingQueued();
    }

    public function test_forgot_password_fails_with_invalid_email_format(): void
    {
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'not-an-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error');
    }

    public function test_reset_password_success(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'user@example.com']);

        Otp::create([
            'user_id'    => $user->id,
            'channel'    => 'email',
            'purpose'    => 'password_reset',
            'code'       => '12345',
            'expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email'    => 'user@example.com',
            'code'     => '12345',
            'password' => 'NewSecurePass456!',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $user->refresh();
        $this->assertTrue(Hash::check('NewSecurePass456!', $user->password));
    }

    public function test_reset_password_revokes_all_refresh_tokens(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'user@example.com']);

        RefreshToken::create([
            'user_id'    => $user->id,
            'token'      => 'existing-token-uuid',
            'expires_at' => now()->addDays(7),
        ]);

        Otp::create([
            'user_id'    => $user->id,
            'channel'    => 'email',
            'purpose'    => 'password_reset',
            'code'       => '12345',
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->postJson('/api/v1/auth/reset-password', [
            'email'    => 'user@example.com',
            'code'     => '12345',
            'password' => 'NewSecurePass456!',
        ]);

        $this->assertNotNull(
            RefreshToken::where('token', 'existing-token-uuid')->value('revoked_at')
        );
    }

    public function test_reset_password_fails_with_wrong_code(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);

        Otp::create([
            'user_id'    => $user->id,
            'channel'    => 'email',
            'purpose'    => 'password_reset',
            'code'       => '12345',
            'expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email'    => 'user@example.com',
            'code'     => '99999',
            'password' => 'NewSecurePass456!',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error');
    }

    public function test_reset_password_fails_with_expired_otp(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);

        Otp::create([
            'user_id'    => $user->id,
            'channel'    => 'email',
            'purpose'    => 'password_reset',
            'code'       => '12345',
            'expires_at' => now()->subMinute(),
        ]);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email'    => 'user@example.com',
            'code'     => '12345',
            'password' => 'NewSecurePass456!',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error');
    }

    public function test_reset_password_fails_for_unregistered_email(): void
    {
        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email'    => 'nobody@example.com',
            'code'     => '12345',
            'password' => 'NewSecurePass456!',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error');
    }

    public function test_reset_password_fails_with_weak_password(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);

        Otp::create([
            'user_id'    => $user->id,
            'channel'    => 'email',
            'purpose'    => 'password_reset',
            'code'       => '12345',
            'expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email'    => 'user@example.com',
            'code'     => '12345',
            'password' => '123',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error');
    }

    public function test_can_login_with_new_password_after_reset(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'user@example.com']);

        Otp::create([
            'user_id'    => $user->id,
            'channel'    => 'email',
            'purpose'    => 'password_reset',
            'code'       => '12345',
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->postJson('/api/v1/auth/reset-password', [
            'email'    => 'user@example.com',
            'code'     => '12345',
            'password' => 'NewSecurePass456!',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'user@example.com',
            'password' => 'NewSecurePass456!',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');
    }
}

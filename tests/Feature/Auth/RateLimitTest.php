<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('register|' . '127.0.0.1');
        RateLimiter::clear('login|' . '127.0.0.1');
        RateLimiter::clear('forgot-password|' . '127.0.0.1');
        RateLimiter::clear('reset-password|' . '127.0.0.1');
    }

    public function test_login_rate_limited_after_10_attempts(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'email'    => 'user@example.com',
                'password' => 'wrongpassword',
            ]);
        }

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'user@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(429)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'Too many requests. Please try again later.');
    }

    public function test_register_rate_limited_after_5_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/register', [
                'name'     => 'User ' . $i,
                'email'    => "user{$i}@example.com",
                'password' => 'NewSecurePass456!',
            ]);
        }

        $response = $this->postJson('/api/v1/auth/register', [
            'name'     => 'User X',
            'email'    => 'userx@example.com',
            'password' => 'NewSecurePass456!',
        ]);

        $response->assertStatus(429)
            ->assertJsonPath('status', 'error');
    }

    public function test_forgot_password_rate_limited_after_3_attempts(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/v1/auth/forgot-password', [
                'email' => 'user@example.com',
            ]);
        }

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'user@example.com',
        ]);

        $response->assertStatus(429)
            ->assertJsonPath('status', 'error');
    }

    public function test_reset_password_rate_limited_after_5_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/reset-password', [
                'email'    => 'user@example.com',
                'code'     => '00000',
                'password' => 'NewSecurePass456!',
            ]);
        }

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email'    => 'user@example.com',
            'code'     => '00000',
            'password' => 'NewSecurePass456!',
        ]);

        $response->assertStatus(429)
            ->assertJsonPath('status', 'error');
    }

    public function test_rate_limit_response_format_is_consistent(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'email'    => 'user@example.com',
                'password' => 'wrong',
            ]);
        }

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'user@example.com',
            'password' => 'wrong',
        ]);

        $response->assertStatus(429)
            ->assertJsonStructure(['status', 'message', 'data'])
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('data', null);
    }
}

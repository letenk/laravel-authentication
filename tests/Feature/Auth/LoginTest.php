<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_success(): void
    {
        User::factory()->create([
            'email'    => 'user@example.com',
            'password' => 'NewSecurePass456!',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'user@example.com',
            'password' => 'NewSecurePass456!',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'data' => ['token', 'token_type', 'expires_in'],
            ])
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonMissing(['data' => ['user']]);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create(['email' => 'user@example.com']);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'user@example.com',
            'password' => 'WrongPassword1!',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('status', 'error');
    }

    public function test_login_fails_with_nonexistent_email(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'nobody@example.com',
            'password' => 'AnyPassword1!',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('status', 'error');
    }

    public function test_login_fails_with_missing_fields(): void
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error');
    }

    public function test_me_returns_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'api');

        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_me_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401)
            ->assertJsonPath('status', 'error');
    }

    public function test_logout_invalidates_token(): void
    {
        User::factory()->create([
            'email'    => 'user@example.com',
            'password' => 'NewSecurePass456!',
        ]);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email'    => 'user@example.com',
            'password' => 'NewSecurePass456!',
        ]);

        $token = $loginResponse->json('data.token');

        $this->withToken($token)->postJson('/api/v1/auth/logout')
            ->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $this->app['auth']->forgetGuards();

        // Token sudah di-blacklist — request berikutnya harus 401
        $this->withToken($token)->getJson('/api/v1/auth/me')
            ->assertStatus(401);
    }
}

<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RefreshTokenTest extends TestCase
{
    use RefreshDatabase;

    private function login(): array
    {
        User::factory()->create([
            'email'    => 'user@example.com',
            'password' => 'NewSecurePass456!',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'user@example.com',
            'password' => 'NewSecurePass456!',
        ]);

        return [
            'token'         => $response->json('data.token'),
            'refresh_token' => $response->json('data.refresh_token'),
        ];
    }

    public function test_login_returns_refresh_token(): void
    {
        $result = $this->login();

        $this->assertNotEmpty($result['token']);
        $this->assertNotEmpty($result['refresh_token']);
        $this->assertDatabaseHas('refresh_tokens', [
            'token' => $result['refresh_token'],
        ]);
    }

    public function test_refresh_returns_new_token_pair(): void
    {
        $tokens = $this->login();

        $response = $this->postJson('/api/v1/auth/refresh', [
            'refresh_token' => $tokens['refresh_token'],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'data' => ['token', 'token_type', 'expires_in', 'refresh_token'],
            ]);

        $newRefreshToken = $response->json('data.refresh_token');
        $this->assertNotEquals($tokens['refresh_token'], $newRefreshToken);
    }

    public function test_old_refresh_token_cannot_be_used_after_rotation(): void
    {
        $tokens = $this->login();

        $this->postJson('/api/v1/auth/refresh', [
            'refresh_token' => $tokens['refresh_token'],
        ])->assertStatus(200);

        $this->postJson('/api/v1/auth/refresh', [
            'refresh_token' => $tokens['refresh_token'],
        ])->assertStatus(401)
            ->assertJsonPath('status', 'error');
    }

    public function test_old_token_marked_as_revoked_with_replaced_by_after_rotation(): void
    {
        $tokens = $this->login();

        $refreshResponse = $this->postJson('/api/v1/auth/refresh', [
            'refresh_token' => $tokens['refresh_token'],
        ]);

        $newRefreshToken = $refreshResponse->json('data.refresh_token');

        $this->assertDatabaseHas('refresh_tokens', [
            'token'              => $tokens['refresh_token'],
            'replaced_by_token'  => $newRefreshToken,
        ]);

        $this->assertNotNull(
            \App\Models\RefreshToken::query()
                ->filterByToken($tokens['refresh_token'])
                ->first()
                ?->revoked_at
        );
    }

    public function test_refresh_fails_with_invalid_token(): void
    {
        $this->postJson('/api/v1/auth/refresh', [
            'refresh_token' => 'invalid-token-string',
        ])->assertStatus(401)
            ->assertJsonPath('status', 'error');
    }

    public function test_logout_revokes_refresh_token(): void
    {
        $tokens = $this->login();

        $this->postJson('/api/v1/auth/logout', [
            'refresh_token' => $tokens['refresh_token'],
        ])->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $this->assertNotNull(
            \App\Models\RefreshToken::query()
                ->filterByToken($tokens['refresh_token'])
                ->first()
                ?->revoked_at
        );
    }

    public function test_logout_fails_with_already_revoked_token(): void
    {
        $tokens = $this->login();

        $this->postJson('/api/v1/auth/logout', [
            'refresh_token' => $tokens['refresh_token'],
        ])->assertStatus(200);

        $this->postJson('/api/v1/auth/logout', [
            'refresh_token' => $tokens['refresh_token'],
        ])->assertStatus(401)
            ->assertJsonPath('status', 'error');
    }

    public function test_refresh_fails_with_missing_token(): void
    {
        $this->postJson('/api/v1/auth/refresh', [])
            ->assertStatus(422)
            ->assertJsonPath('status', 'error');
    }
}

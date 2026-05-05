<?php

namespace Tests\Feature\Auth;

use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_sessions_returns_active_sessions(): void
    {
        $user = User::factory()->create();

        [$token] = $this->loginAndGetTokens($user);

        $response = $this->withToken($token)->getJson('/api/v1/user/sessions');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure(['data' => [['id', 'device_name', 'ip_address', 'user_agent', 'created_at', 'expires_at']]]);
    }

    public function test_list_sessions_does_not_include_token_field(): void
    {
        $user = User::factory()->create();

        [$token] = $this->loginAndGetTokens($user);

        $response = $this->withToken($token)->getJson('/api/v1/user/sessions');

        $response->assertStatus(200);

        $session = $response->json('data.0');
        $this->assertArrayNotHasKey('token', $session);
        $this->assertArrayNotHasKey('revoked_at', $session);
    }

    public function test_list_sessions_does_not_return_revoked_sessions(): void
    {
        $user = User::factory()->create();

        [$token, $refreshToken] = $this->loginAndGetTokens($user);

        $this->withToken($token)->postJson('/api/v1/auth/logout', [
            'refresh_token' => $refreshToken,
        ]);

        [$newToken] = $this->loginAndGetTokens($user);

        $response = $this->withToken($newToken)->getJson('/api/v1/user/sessions');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_multiple_devices_appear_as_separate_sessions(): void
    {
        $user = User::factory()->create();

        [$tokenA] = $this->loginAndGetTokens($user, 'Device A');
        $this->loginAndGetTokens($user, 'Device B');

        $response = $this->withToken($tokenA)->getJson('/api/v1/user/sessions');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_revoke_session_success(): void
    {
        $user = User::factory()->create();

        [$token] = $this->loginAndGetTokens($user);

        $sessions   = $this->withToken($token)->getJson('/api/v1/user/sessions');
        $sessionId  = $sessions->json('data.0.id');

        $response = $this->withToken($token)->deleteJson("/api/v1/user/sessions/{$sessionId}");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $this->assertNotNull(RefreshToken::find($sessionId)->revoked_at);
    }

    public function test_cannot_revoke_another_users_session(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        [$tokenA] = $this->loginAndGetTokens($userA);

        $sessionB = RefreshToken::create([
            'user_id'    => $userB->id,
            'token'      => 'userb-session-uuid',
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->withToken($tokenA)->deleteJson("/api/v1/user/sessions/{$sessionB->id}");

        $response->assertStatus(404)
            ->assertJsonPath('status', 'error');

        $this->assertNull($sessionB->fresh()->revoked_at);
    }

    public function test_revoke_nonexistent_session_returns_404(): void
    {
        $user = User::factory()->create();

        [$token] = $this->loginAndGetTokens($user);

        $response = $this->withToken($token)->deleteJson('/api/v1/user/sessions/99999');

        $response->assertStatus(404)
            ->assertJsonPath('status', 'error');
    }

    public function test_logout_all_revokes_all_sessions(): void
    {
        $user = User::factory()->create();

        [$tokenA] = $this->loginAndGetTokens($user, 'Device A');
        $this->loginAndGetTokens($user, 'Device B');

        $response = $this->withToken($tokenA)->postJson('/api/v1/auth/logout-all');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseMissing('refresh_tokens', [
            'user_id'    => $user->id,
            'revoked_at' => null,
        ]);
    }

    public function test_sessions_requires_auth(): void
    {
        $this->getJson('/api/v1/user/sessions')->assertStatus(401);
        $this->deleteJson('/api/v1/user/sessions/1')->assertStatus(401);
        $this->postJson('/api/v1/auth/logout-all')->assertStatus(401);
    }

    private function loginAndGetTokens(User $user, string $deviceName = 'Test Device'): array
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email'       => $user->email,
            'password'    => 'password',
            'device_name' => $deviceName,
        ]);

        return [
            $response->json('data.token'),
            $response->json('data.refresh_token'),
        ];
    }
}

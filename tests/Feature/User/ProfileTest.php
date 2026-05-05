<?php

namespace Tests\Feature\User;

use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_name_success(): void
    {
        $user  = User::factory()->create();
        $token = $this->getToken($user);

        $response = $this->withToken($token)->putJson('/api/v1/user/profile', [
            'name' => 'New Name',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'New Name']);
    }

    public function test_update_phone_success(): void
    {
        $user  = User::factory()->create();
        $token = $this->getToken($user);

        $response = $this->withToken($token)->putJson('/api/v1/user/profile', [
            'phone' => '+628123456789',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'phone' => '628123456789']);
    }

    public function test_update_profile_with_duplicate_phone_fails(): void
    {
        User::factory()->create(['phone' => '628999999999']);
        $user  = User::factory()->create();
        $token = $this->getToken($user);

        $response = $this->withToken($token)->putJson('/api/v1/user/profile', [
            'phone' => '+628999999999',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error');
    }

    public function test_update_profile_own_phone_does_not_trigger_duplicate(): void
    {
        $user  = User::factory()->create(['phone' => '628123456789']);
        $token = $this->getToken($user);

        $response = $this->withToken($token)->putJson('/api/v1/user/profile', [
            'phone' => '+628123456789',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');
    }

    public function test_change_password_success(): void
    {
        $user  = User::factory()->create();
        $token = $this->getToken($user);

        $response = $this->withToken($token)->putJson('/api/v1/user/password', [
            'current_password' => 'password',
            'password'         => 'NewSecurePass456!',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $user->refresh();
        $this->assertTrue(Hash::check('NewSecurePass456!', $user->password));
    }

    public function test_change_password_revokes_all_refresh_tokens(): void
    {
        $user  = User::factory()->create();
        $token = $this->getToken($user);

        $this->withToken($token)->putJson('/api/v1/user/password', [
            'current_password' => 'password',
            'password'         => 'NewSecurePass456!',
        ]);

        $this->assertDatabaseMissing('refresh_tokens', [
            'user_id'    => $user->id,
            'revoked_at' => null,
        ]);
    }

    public function test_change_password_fails_with_wrong_current_password(): void
    {
        $user  = User::factory()->create();
        $token = $this->getToken($user);

        $response = $this->withToken($token)->putJson('/api/v1/user/password', [
            'current_password' => 'wrongpassword',
            'password'         => 'NewSecurePass456!',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error');
    }

    public function test_change_password_fails_with_weak_new_password(): void
    {
        $user  = User::factory()->create();
        $token = $this->getToken($user);

        $response = $this->withToken($token)->putJson('/api/v1/user/password', [
            'current_password' => 'password',
            'password'         => '123',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error');
    }

    public function test_delete_account_soft_deletes_user(): void
    {
        $user  = User::factory()->create();
        $token = $this->getToken($user);

        $response = $this->withToken($token)->deleteJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_delete_account_revokes_all_tokens(): void
    {
        $user  = User::factory()->create();
        $token = $this->getToken($user);

        $this->withToken($token)->deleteJson('/api/v1/auth/me');

        $this->assertDatabaseMissing('refresh_tokens', [
            'user_id'    => $user->id,
            'revoked_at' => null,
        ]);
    }

    public function test_deleted_user_cannot_login(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $user->delete();

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'user@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('status', 'error');
    }

    public function test_profile_endpoints_require_auth(): void
    {
        $this->putJson('/api/v1/user/profile', [])->assertStatus(401);
        $this->putJson('/api/v1/user/password', [])->assertStatus(401);
        $this->deleteJson('/api/v1/auth/me')->assertStatus(401);
    }

    private function getToken(User $user): string
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => $user->email,
            'password' => 'password',
        ]);

        return $response->json('data.token');
    }
}

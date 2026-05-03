<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_success(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name'     => 'Test User',
            'email'    => 'test@example.com',
            'password' => 'NewSecurePass456!',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => ['id', 'name', 'email', 'login_type', 'is_verified', 'created_at'],
            ])
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.email', 'test@example.com')
            ->assertJsonPath('data.login_type', 'email')
            ->assertJsonPath('data.is_verified', false);

        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    public function test_register_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->postJson('/api/v1/auth/register', [
            'name'     => 'Another User',
            'email'    => 'taken@example.com',
            'password' => 'NewSecurePass456!',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error')
            ->assertJsonStructure(['status', 'message', 'data' => ['email']]);
    }

    public function test_register_fails_with_missing_fields(): void
    {
        $response = $this->postJson('/api/v1/auth/register', []);

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error')
            ->assertJsonStructure(['status', 'message', 'data' => ['name', 'email', 'password']]);
    }

    public function test_register_fails_with_invalid_email(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name'     => 'Test User',
            'email'    => 'not-an-email',
            'password' => 'NewSecurePass456!',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error')
            ->assertJsonStructure(['status', 'message', 'data' => ['email']]);
    }

    public function test_register_fails_with_short_password(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name'     => 'Test User',
            'email'    => 'test@example.com',
            'password' => 'Short1!',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error')
            ->assertJsonStructure(['status', 'message', 'data' => ['password']]);
    }

    public function test_register_fails_with_weak_password(): void
    {
        $weakPasswords = [
            'alllowercase1!',  // tidak ada huruf besar
            'ALLUPPERCASE1!',  // tidak ada huruf kecil
            'NoNumbers!!',     // tidak ada angka
            'NoSymbols123',    // tidak ada simbol
        ];

        foreach ($weakPasswords as $password) {
            $response = $this->postJson('/api/v1/auth/register', [
                'name'     => 'Test User',
                'email'    => 'test@example.com',
                'password' => $password,
            ]);

            $response->assertStatus(422)
                ->assertJsonPath('status', 'error')
                ->assertJsonStructure(['status', 'message', 'data' => ['password']]);
        }
    }

    public function test_password_is_hashed_in_database(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name'     => 'Test User',
            'email'    => 'test@example.com',
            'password' => 'NewSecurePass456!',
        ]);

        $user = User::where('email', 'test@example.com')->first();

        $this->assertNotEquals('NewSecurePass456!', $user->password);
        $this->assertTrue(password_verify('NewSecurePass456!', $user->password));
    }
}

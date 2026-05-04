<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhoneAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_with_phone_success(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name'     => 'Test User',
            'phone'    => '+628123456789',
            'password' => 'NewSecurePass456!',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success');

        // stored as E.164 digits-only (no '+')
        $this->assertDatabaseHas('users', [
            'phone'      => '628123456789',
            'login_type' => 'phone',
        ]);
    }

    public function test_register_with_different_country_phone(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name'     => 'Test User',
            'phone'    => '+12025550123',
            'password' => 'NewSecurePass456!',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'phone'      => '12025550123',
            'login_type' => 'phone',
        ]);
    }

    public function test_register_with_email_sets_email_login_type(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name'     => 'Test User',
            'email'    => 'user@example.com',
            'password' => 'NewSecurePass456!',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'email'      => 'user@example.com',
            'login_type' => 'email',
        ]);
    }

    public function test_register_fails_without_email_or_phone(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name'     => 'Test User',
            'password' => 'NewSecurePass456!',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error');
    }

    public function test_register_fails_with_invalid_phone_number(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name'     => 'Test User',
            'phone'    => '+62123',
            'password' => 'NewSecurePass456!',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error');
    }

    public function test_register_fails_without_country_code(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name'     => 'Test User',
            'phone'    => '08123456789',
            'password' => 'NewSecurePass456!',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error');
    }

    public function test_register_fails_with_duplicate_phone(): void
    {
        User::factory()->create(['phone' => '628123456789']);

        $response = $this->postJson('/api/v1/auth/register', [
            'name'     => 'Another User',
            'phone'    => '+628123456789',
            'password' => 'NewSecurePass456!',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error')
            ->assertJsonStructure(['data' => ['phone']]);
    }

    public function test_login_with_phone_success(): void
    {
        User::factory()->create([
            'phone'    => '628123456789',
            'password' => 'NewSecurePass456!',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'phone'    => '+628123456789',
            'password' => 'NewSecurePass456!',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'data' => ['token', 'token_type', 'expires_in', 'refresh_token'],
            ]);
    }

    public function test_login_with_phone_fails_with_wrong_password(): void
    {
        User::factory()->create(['phone' => '628123456789']);

        $response = $this->postJson('/api/v1/auth/login', [
            'phone'    => '+628123456789',
            'password' => 'WrongPassword1!',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('status', 'error');
    }

    public function test_login_fails_without_email_or_phone(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'password' => 'NewSecurePass456!',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error');
    }
}

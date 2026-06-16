<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_receives_an_otp_without_a_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name'                  => 'Test User',
            'email'                 => 'winny.test.user@gmail.com',
            'password'              => 'Winny#Land7Pass',
            'password_confirmation' => 'Winny#Land7Pass',
        ]);

        // No token is issued — the account is not usable until verified.
        $response->assertStatus(201)
            ->assertJsonStructure(['email', 'message'])
            ->assertJsonMissing(['token' => true])
            ->assertJsonPath('email', 'winny.test.user@gmail.com');

        $this->assertDatabaseHas('users', [
            'email'             => 'winny.test.user@gmail.com',
            'email_verified_at' => null,
        ]);
    }

    public function test_user_can_login(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => $user->email,
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['user', 'token']);
    }

    public function test_login_fails_with_wrong_credentials(): void
    {
        $user = User::factory()->create(['password' => bcrypt('correct')]);

        $this->postJson('/api/v1/auth/login', [
            'email'    => $user->email,
            'password' => 'wrong',
        ])->assertStatus(422);
    }

    public function test_authenticated_user_can_get_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('user.email', $user->email);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->getJson('/api/v1/auth/me')->assertStatus(401);
    }

    public function test_user_can_logout(): void
    {
        $user  = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/auth/logout')
            ->assertOk();
    }
}

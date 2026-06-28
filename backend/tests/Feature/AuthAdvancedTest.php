<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Extended authentication tests covering session management,
 * OTP expiry, inactive account blocking, and logout-all.
 */
class AuthAdvancedTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'password'  => Hash::make('Password1!'),
            'is_active' => false,
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email'    => $user->email,
            'password' => 'Password1!',
        ])->assertStatus(422);
    }

    public function test_login_returns_token_for_verified_user(): void
    {
        $user = User::factory()->create(['password' => Hash::make('Password1!')]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => $user->email,
            'password' => 'Password1!',
        ]);

        $response->assertOk()->assertJsonStructure(['token', 'user']);
        $this->assertNotEmpty($response->json('token'));
    }

    public function test_unverified_user_login_returns_403_with_email_flag(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create(['password' => Hash::make('Password1!')]);

        $this->postJson('/api/v1/auth/login', [
            'email'    => $user->email,
            'password' => 'Password1!',
        ])
        ->assertStatus(403)
        ->assertJsonPath('email_unverified', true)
        ->assertJsonPath('email', $user->email);
    }

    public function test_logout_revokes_current_token_only(): void
    {
        $user   = User::factory()->create();
        $result1 = $user->createToken('device-1');
        $user->createToken('device-2');

        $this->withToken($result1->plainTextToken)->postJson('/api/v1/auth/logout')->assertOk();

        // token1 must no longer exist in DB
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $result1->accessToken->id]);
        // token2 must still exist (only 1 token revoked)
        $this->assertEquals(1, $user->tokens()->count());
    }

    public function test_logout_all_revokes_every_token(): void
    {
        $user = User::factory()->create();
        $user->createToken('device-1');
        $user->createToken('device-2');

        $this->actingAs($user)->postJson('/api/v1/auth/logout-all')->assertOk();

        $this->assertEquals(0, $user->tokens()->count());
    }

    public function test_expired_otp_cannot_be_used(): void
    {
        $user = User::factory()->unverified()->create();

        $otp = $user->generateEmailOtp();

        // Artificially expire the OTP
        $user->forceFill(['email_otp_expires_at' => now()->subMinute()])->save();

        $this->postJson('/api/v1/auth/verify-otp', [
            'email' => $user->email,
            'otp'   => $otp,
        ])->assertStatus(422);

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_otp_is_locked_after_max_attempts(): void
    {
        $user = User::factory()->unverified()->create();
        $otp  = $user->generateEmailOtp();

        // Exhaust attempts directly via the model method, bypassing rate limiting
        for ($i = 0; $i < User::OTP_MAX_ATTEMPTS; $i++) {
            $user->verifyEmailOtp('000000');
        }

        $this->assertEquals(User::OTP_MAX_ATTEMPTS, $user->fresh()->email_otp_attempts);

        // Even the correct OTP must now be rejected (attempts locked)
        $this->assertFalse($user->verifyEmailOtp($otp));
    }

    public function test_wrong_password_does_not_reveal_account_existence(): void
    {
        $existingUser = User::factory()->create(['email' => 'real@example.com']);

        $responseReal  = $this->postJson('/api/v1/auth/login', [
            'email'    => 'real@example.com',
            'password' => 'WrongPassword1!',
        ]);
        $responseGhost = $this->postJson('/api/v1/auth/login', [
            'email'    => 'ghost@example.com',
            'password' => 'WrongPassword1!',
        ]);

        // Both return 422; response bodies should not differ in a way that reveals existence
        $this->assertEquals(422, $responseReal->getStatusCode());
        $this->assertEquals(422, $responseGhost->getStatusCode());
    }

    public function test_password_change_requires_current_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('OldPass1!')]);

        $this->actingAs($user)
            ->patchJson('/api/v1/auth/password', [
                'current_password'      => 'WrongOldPass1!',
                'password'              => 'NewPass1!SecureEnough',
                'password_confirmation' => 'NewPass1!SecureEnough',
            ])
            ->assertStatus(422);
    }

    public function test_password_change_revokes_other_tokens(): void
    {
        $user    = User::factory()->create(['password' => Hash::make('OldPass1!')]);
        $result1 = $user->createToken('current');
        $result2 = $user->createToken('other-device');

        $this->withToken($result1->plainTextToken)
            ->patchJson('/api/v1/auth/password', [
                'current_password'      => 'OldPass1!',
                'password'              => 'NewPass1!SecureEnough',
                'password_confirmation' => 'NewPass1!SecureEnough',
            ])
            ->assertOk();

        // Other device's token must be revoked from DB
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $result2->accessToken->id,
        ]);
    }

    public function test_resend_otp_does_not_reveal_email_existence(): void
    {
        // Resend for a non-existent email still returns 200 (no enumeration)
        $this->postJson('/api/v1/auth/resend-otp', ['email' => 'ghost@doesnotexist.example'])
            ->assertOk();
    }

    public function test_profile_update_only_allows_whitelisted_fields(): void
    {
        $user = User::factory()->create(['email' => 'original@example.com']);

        $this->actingAs($user)
            ->patchJson('/api/v1/auth/me', [
                'name'     => 'Updated Name',
                'email'    => 'hacker@evil.com', // must be ignored
                'password' => 'OverriddenPass1!', // must be ignored
                'role'     => 'admin',            // must be ignored
            ])
            ->assertOk();

        $fresh = $user->fresh();
        $this->assertEquals('Updated Name', $fresh->name);
        $this->assertEquals('original@example.com', $fresh->email);
        $this->assertEquals('customer', $fresh->role);
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\VerifyEmailOtp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailVerificationOtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_sends_an_otp_email(): void
    {
        Notification::fake();

        $this->postJson('/api/v1/auth/register', [
            'name'                  => 'Otp User',
            'email'                 => 'winny.otp.user@gmail.com',
            'password'              => 'Winny#Land7Pass',
            'password_confirmation' => 'Winny#Land7Pass',
        ])->assertStatus(201);

        $user = User::where('email', 'winny.otp.user@gmail.com')->firstOrFail();

        Notification::assertSentTo($user, VerifyEmailOtp::class);
        $this->assertNotNull($user->email_otp);
        $this->assertNull($user->email_verified_at);
    }

    public function test_user_can_verify_with_correct_otp_and_receives_a_token(): void
    {
        $user = User::factory()->unverified()->create();
        $otp  = $user->generateEmailOtp();

        $this->postJson('/api/v1/auth/verify-otp', [
            'email' => $user->email,
            'otp'   => $otp,
        ])
            ->assertOk()
            ->assertJsonStructure(['user' => ['id', 'email'], 'token']);

        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertNull($user->fresh()->email_otp);
    }

    public function test_verification_fails_with_wrong_otp(): void
    {
        $user = User::factory()->unverified()->create();
        $user->generateEmailOtp();

        $this->postJson('/api/v1/auth/verify-otp', [
            'email' => $user->email,
            'otp'   => '000000',
        ])->assertStatus(422);

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_expired_otp_is_rejected(): void
    {
        $user = User::factory()->unverified()->create();
        $otp  = $user->generateEmailOtp();

        $user->forceFill(['email_otp_expires_at' => now()->subMinute()])->save();

        $this->postJson('/api/v1/auth/verify-otp', [
            'email' => $user->email,
            'otp'   => $otp,
        ])->assertStatus(422);

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_login_is_blocked_for_unverified_users_and_resends_a_code(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create(['password' => bcrypt('Winny#Land7Pass')]);

        $this->postJson('/api/v1/auth/login', [
            'email'    => $user->email,
            'password' => 'Winny#Land7Pass',
        ])
            ->assertStatus(403)
            ->assertJsonPath('email_unverified', true)
            ->assertJsonPath('email', $user->email);

        Notification::assertSentTo($user, VerifyEmailOtp::class);
    }

    public function test_resend_otp_sends_a_new_code_to_unverified_user(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        $this->postJson('/api/v1/auth/resend-otp', ['email' => $user->email])
            ->assertOk();

        Notification::assertSentTo($user, VerifyEmailOtp::class);
    }
}

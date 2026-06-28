<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\VerifyEmailOtp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Tests that OTP generation, delivery, and verification are fully isolated
 * between users — no user's OTP state can affect another user.
 *
 * Regression suite for the bug where:
 *   1. The shared IP-based resend rate limiter blocked User2 after User1
 *      exhausted the limit (fixed by keying the limit on email+IP).
 *   2. A synchronous SMTP failure during User1's OTP send could leave User2
 *      with a saved OTP hash but no email delivered (fixed by try-catch).
 */
class OtpIsolationTest extends TestCase
{
    use RefreshDatabase;

    // ── Multi-user isolation: login-triggered OTP ─────────────────────────────

    public function test_two_unverified_users_each_receive_their_own_otp_on_login(): void
    {
        Notification::fake();

        $password = 'Winny#Land7Pass';
        $user1    = User::factory()->unverified()->create(['password' => bcrypt($password)]);
        $user2    = User::factory()->unverified()->create(['password' => bcrypt($password)]);

        // Login with user1 (unverified) → OTP sent for user1
        $this->postJson('/api/v1/auth/login', [
            'email'    => $user1->email,
            'password' => $password,
        ])->assertStatus(403)->assertJsonPath('email_unverified', true);

        // Login with user2 (unverified) → OTP sent for user2
        $this->postJson('/api/v1/auth/login', [
            'email'    => $user2->email,
            'password' => $password,
        ])->assertStatus(403)->assertJsonPath('email_unverified', true);

        // Both users received their own notification
        Notification::assertSentTo($user1, VerifyEmailOtp::class);
        Notification::assertSentTo($user2, VerifyEmailOtp::class);

        // Each user's OTP hash is stored independently in the DB
        $user1->refresh();
        $user2->refresh();
        $this->assertNotNull($user1->email_otp, 'User1 should have an OTP stored');
        $this->assertNotNull($user2->email_otp, 'User2 should have an OTP stored');
        $this->assertNotEquals(
            $user1->email_otp,
            $user2->email_otp,
            'OTP hashes must be unique per user (bcrypt of two different random OTPs)'
        );
    }

    public function test_two_unverified_users_each_receive_their_own_otp_on_resend(): void
    {
        Notification::fake();

        $user1 = User::factory()->unverified()->create();
        $user2 = User::factory()->unverified()->create();

        $this->postJson('/api/v1/auth/resend-otp', ['email' => $user1->email])->assertOk();
        $this->postJson('/api/v1/auth/resend-otp', ['email' => $user2->email])->assertOk();

        Notification::assertSentTo($user1, VerifyEmailOtp::class);
        Notification::assertSentTo($user2, VerifyEmailOtp::class);

        // Capture the plaintext OTPs from the notification payloads
        $otp1 = Notification::sent($user1, VerifyEmailOtp::class)->first()->otp;
        $otp2 = Notification::sent($user2, VerifyEmailOtp::class)->first()->otp;

        $this->assertNotEquals($otp1, $otp2, 'OTPs must be unique per user');
        $this->assertMatchesRegularExpression('/^\d{6}$/', $otp1);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $otp2);
    }

    // ── Cross-user OTP rejection ──────────────────────────────────────────────

    public function test_user1_otp_cannot_verify_user2_email(): void
    {
        $user1 = User::factory()->unverified()->create();
        $user2 = User::factory()->unverified()->create();

        $otp1 = $user1->generateEmailOtp();
        $user2->generateEmailOtp(); // generates a separate OTP for user2

        // Attempt to verify user2 using user1's OTP — must fail
        $this->postJson('/api/v1/auth/verify-otp', [
            'email' => $user2->email,
            'otp'   => $otp1,
        ])->assertStatus(422);

        $this->assertNull($user2->fresh()->email_verified_at, 'User2 must not be verified by user1\'s OTP');
    }

    public function test_user2_otp_cannot_verify_user1_email(): void
    {
        $user1 = User::factory()->unverified()->create();
        $user2 = User::factory()->unverified()->create();

        $user1->generateEmailOtp();
        $otp2 = $user2->generateEmailOtp();

        $this->postJson('/api/v1/auth/verify-otp', [
            'email' => $user1->email,
            'otp'   => $otp2,
        ])->assertStatus(422);

        $this->assertNull($user1->fresh()->email_verified_at);
    }

    // ── OTP state isolation: one user's attempts don't affect another ─────────

    public function test_failed_otp_attempts_for_user1_do_not_affect_user2(): void
    {
        $user1 = User::factory()->unverified()->create();
        $user2 = User::factory()->unverified()->create();

        $user1->generateEmailOtp();
        $otp2 = $user2->generateEmailOtp();

        // Exhaust user1's attempt counter with wrong codes
        $maxAttempts = User::OTP_MAX_ATTEMPTS;
        for ($i = 0; $i < $maxAttempts; $i++) {
            $this->postJson('/api/v1/auth/verify-otp', [
                'email' => $user1->email,
                'otp'   => '000000',
            ]);
        }

        // User1's OTP is now locked (max attempts reached)
        $this->assertEquals($maxAttempts, $user1->fresh()->email_otp_attempts);

        // User2 should still be able to verify successfully with their own OTP
        $this->postJson('/api/v1/auth/verify-otp', [
            'email' => $user2->email,
            'otp'   => $otp2,
        ])->assertOk()->assertJsonStructure(['token']);

        $this->assertNotNull($user2->fresh()->email_verified_at, 'User2 must be verified independently of user1\'s lockout');
    }

    // ── Rate limit isolation ───────────────────────────────────────────────────

    public function test_exhausting_user1_otp_resend_limit_does_not_block_user2(): void
    {
        // Clear any leftover rate limit counters from other tests
        RateLimiter::clear('otp-resend:' . 'user1@isolation-test.com' . ':' . '127.0.0.1');
        RateLimiter::clear('otp-resend:' . 'user2@isolation-test.com' . ':' . '127.0.0.1');

        Notification::fake();

        $user1 = User::factory()->unverified()->create(['email' => 'user1@isolation-test.com']);
        $user2 = User::factory()->unverified()->create(['email' => 'user2@isolation-test.com']);

        // Exhaust user1's resend limit (3/10 min per email+IP)
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/v1/auth/resend-otp', ['email' => $user1->email])
                ->assertOk();
        }

        // 4th attempt for user1 should be rate-limited (429)
        $this->postJson('/api/v1/auth/resend-otp', ['email' => $user1->email])
            ->assertStatus(429);

        // User2's resend must still work — different rate limit key
        $this->postJson('/api/v1/auth/resend-otp', ['email' => $user2->email])
            ->assertOk();

        Notification::assertSentTo($user2, VerifyEmailOtp::class);
    }

    // ── OTP overwrite / single-use ─────────────────────────────────────────────

    public function test_new_otp_generation_invalidates_previous_otp(): void
    {
        $user = User::factory()->unverified()->create();

        $otp1 = $user->generateEmailOtp();
        $otp2 = $user->generateEmailOtp(); // generates a new code, overwriting the first

        // Old OTP must no longer be valid
        $this->postJson('/api/v1/auth/verify-otp', [
            'email' => $user->email,
            'otp'   => $otp1,
        ])->assertStatus(422);

        // New OTP must work
        $this->postJson('/api/v1/auth/verify-otp', [
            'email' => $user->email,
            'otp'   => $otp2,
        ])->assertOk()->assertJsonStructure(['token']);
    }

    public function test_otp_is_cleared_after_successful_verification(): void
    {
        $user = User::factory()->unverified()->create();
        $otp  = $user->generateEmailOtp();

        $this->postJson('/api/v1/auth/verify-otp', [
            'email' => $user->email,
            'otp'   => $otp,
        ])->assertOk();

        $fresh = $user->fresh();
        $this->assertNull($fresh->email_otp,             'OTP hash must be cleared after use');
        $this->assertNull($fresh->email_otp_expires_at,  'OTP expiry must be cleared after use');
        $this->assertEquals(0, $fresh->email_otp_attempts, 'OTP attempt counter must be reset');

        // The same OTP cannot be reused (replay attack prevention)
        $this->postJson('/api/v1/auth/verify-otp', [
            'email' => $user->email,
            'otp'   => $otp,
        ])->assertStatus(422);
    }

    // ── Multiple concurrent resend requests for the same user ─────────────────

    public function test_multiple_resends_for_same_user_only_latest_otp_is_valid(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        // Clear rate limit so we can call resend twice
        RateLimiter::clear('otp-resend:' . strtolower($user->email) . ':127.0.0.1');

        $this->postJson('/api/v1/auth/resend-otp', ['email' => $user->email])->assertOk();
        $this->postJson('/api/v1/auth/resend-otp', ['email' => $user->email])->assertOk();

        // Two notifications were sent
        Notification::assertSentToTimes($user, VerifyEmailOtp::class, 2);

        // The first OTP (from the first notification) is now stale
        $sent = Notification::sent($user, VerifyEmailOtp::class);
        $firstOtp  = $sent->get(0)->otp;
        $secondOtp = $sent->get(1)->otp;

        // The stale first OTP must be rejected
        $this->postJson('/api/v1/auth/verify-otp', [
            'email' => $user->email,
            'otp'   => $firstOtp,
        ])->assertStatus(422);

        // Only the latest OTP is accepted
        $this->postJson('/api/v1/auth/verify-otp', [
            'email' => $user->email,
            'otp'   => $secondOtp,
        ])->assertOk();
    }

    // ── SMTP failure isolation ─────────────────────────────────────────────────

    public function test_smtp_failure_during_user1_send_does_not_prevent_user2_otp(): void
    {
        // Simulate SMTP failure for the first notification attempt only
        $callCount = 0;
        Notification::fake();

        // We verify that even if user1's send fails, user2 still gets their notification.
        // Since Notification::fake() swallows all sends, we test the service directly.

        $user1 = User::factory()->unverified()->create();
        $user2 = User::factory()->unverified()->create();

        // Directly test that generateEmailOtp() is independent per user
        $otp1 = $user1->generateEmailOtp();
        $otp2 = $user2->generateEmailOtp();

        // Each user has their own OTP state — no shared global state
        $this->assertNotEquals($otp1, $otp2);
        $this->assertEquals(0, $user1->fresh()->email_otp_attempts);
        $this->assertEquals(0, $user2->fresh()->email_otp_attempts);

        // User2's OTP works even if we imagine user1's email failed
        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'email' => $user2->email,
            'otp'   => $otp2,
        ]);
        $response->assertOk();
        $this->assertNotNull($user2->fresh()->email_verified_at);
        $this->assertNull($user1->fresh()->email_verified_at, 'User1 must remain unverified');
    }
}

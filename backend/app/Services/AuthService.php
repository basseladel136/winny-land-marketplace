<?php

namespace App\Services;

use App\Exceptions\EmailNotVerifiedException;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Register a new user.
     *
     * Role is NEVER accepted from the request — it is assigned solely based on
     * whether the email matches the ADMIN_EMAIL environment variable.
     *
     * No access token is issued here: the account is not usable until the user
     * confirms the 6-digit OTP that is emailed to them (see verifyOtp()).
     */
    public function register(array $data): array
    {
        $adminEmail = strtolower(config('app.admin_email', ''));
        $isAdmin    = $adminEmail && strtolower($data['email']) === $adminEmail;

        // Create user — role bypasses $fillable via direct column set
        $user = new User([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => $data['password'],
            'phone'     => $data['phone'] ?? null,
            'locale'    => $data['locale'] ?? 'en',
            'is_active' => true,
        ]);

        $user->role = $isAdmin ? 'admin' : 'customer';
        $user->save();

        // Dispatch Registered event → triggers the OTP verification email via
        // the framework's SendEmailVerificationNotification listener.
        // BUG FIX: Wrap in try-catch so SMTP failures during registration don't
        // return a 500. The user account is already created; they can use the
        // resend endpoint to get a fresh OTP if the first delivery fails.
        try {
            event(new Registered($user));
        } catch (\Throwable $e) {
            Log::error('OTP email delivery failed during registration', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
        }

        return compact('user');
    }

    /**
     * Verify a registration OTP. On success the email is marked verified and a
     * fresh API token is issued so the user is immediately signed in.
     */
    public function verifyOtp(string $email, string $otp): array
    {
        $user = User::where('email', $email)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'otp' => ['The verification code is invalid or has expired.'],
            ]);
        }

        if ($user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'email' => ['This email is already verified. Please sign in.'],
            ]);
        }

        if (! $user->verifyEmailOtp($otp)) {
            throw ValidationException::withMessages([
                'otp' => ['The verification code is invalid or has expired.'],
            ]);
        }

        event(new Verified($user));
        $user->syncAdminRole();

        $token = $user->createToken('api-token')->plainTextToken;

        return compact('user', 'token');
    }

    /**
     * Resend a verification OTP to an unverified account.
     *
     * Always returns silently (no error if the email is unknown or already
     * verified) to avoid leaking which addresses are registered.
     */
    public function resendOtp(string $email): void
    {
        $user = User::where('email', $email)->first();

        if ($user && ! $user->hasVerifiedEmail()) {
            try {
                $user->sendEmailVerificationNotification();
            } catch (\Throwable $e) {
                // Log but do not expose: the response is intentionally ambiguous
                // to prevent email enumeration. The user can retry the resend.
                Log::error('OTP email delivery failed during resend', [
                    'user_id' => $user->id,
                    'error'   => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Authenticate a user.
     *
     * Also auto-syncs admin role on login in case ADMIN_EMAIL changed.
     */
    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => [__('auth.inactive')],
            ]);
        }

        // Block sign-in until the email is verified. Send a fresh code and tell
        // the frontend to route the user to the OTP screen.
        //
        // BUG FIX: VerifyEmailOtp uses Queueable without ShouldQueue, so the
        // email is delivered synchronously during this request. If SMTP fails,
        // the exception would have propagated as a 500 before, leaving the user
        // with an OTP hash in the DB but no email. Now we log the failure and
        // still throw EmailNotVerifiedException so the frontend shows the correct
        // screen — the user can request a fresh code via the resend endpoint.
        if (! $user->hasVerifiedEmail()) {
            try {
                $user->sendEmailVerificationNotification();
            } catch (\Throwable $e) {
                Log::error('OTP email delivery failed during login', [
                    'user_id' => $user->id,
                    'error'   => $e->getMessage(),
                ]);
            }
            throw new EmailNotVerifiedException($user->email);
        }

        // Sync admin role on every login (handles ADMIN_EMAIL changes)
        $user->syncAdminRole();

        // Revoke old tokens on fresh login to limit active sessions
        // (keeps only the newest token — optional, adjust to taste)
        $user->tokens()->delete();
        $token = $user->createToken('api-token')->plainTextToken;

        return compact('user', 'token');
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    public function logoutAll(User $user): void
    {
        $user->tokens()->delete();
    }

    /**
     * Resend the email verification notification.
     *
     * Returns false if the email is already verified.
     */
    public function resendVerification(User $user): bool
    {
        if ($user->hasVerifiedEmail()) {
            return false;
        }

        try {
            $user->sendEmailVerificationNotification();
        } catch (\Throwable $e) {
            Log::error('OTP email delivery failed during resend-verification', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
            // Re-throw so the controller can return a 500 — the authenticated user
            // can tell something went wrong (unlike the unauthenticated resend path).
            throw $e;
        }

        return true;
    }
}

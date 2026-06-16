<?php

namespace App\Services;

use App\Exceptions\EmailNotVerifiedException;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Hash;
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

        // Dispatch Registered event → triggers the OTP verification email.
        // No token is returned: the user must verify before they can sign in.
        event(new Registered($user));

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
            $user->sendEmailVerificationNotification();
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
        if (! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
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

        $user->sendEmailVerificationNotification();
        return true;
    }
}

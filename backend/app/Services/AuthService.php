<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Register a new user.
     *
     * Role is NEVER accepted from the request — it is assigned solely based on
     * whether the email matches the ADMIN_EMAIL environment variable.
     * A verification email is dispatched immediately after registration.
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

        // Dispatch Registered event → triggers email verification notification
        event(new Registered($user));

        $token = $user->createToken('api-token')->plainTextToken;

        return compact('user', 'token');
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

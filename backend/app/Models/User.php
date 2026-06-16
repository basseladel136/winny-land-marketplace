<?php

namespace App\Models;

use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;
use App\Notifications\VerifyEmailOtp;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, MustVerifyEmailTrait, Notifiable;

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;

    /**
     * Fields that can be mass-assigned.
     *
     * NOTE: 'role' is intentionally omitted — it must only be set
     * programmatically (ADMIN_EMAIL env check) to prevent privilege escalation.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'locale',
        'avatar',
        'address',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'email_otp',
        'email_otp_expires_at',
        'email_otp_attempts',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'    => 'datetime',
            'email_otp_expires_at' => 'datetime',
            'password'             => 'hashed',
            'is_active'            => 'boolean',
        ];
    }

    /** OTP lifetime in minutes. */
    public const OTP_TTL_MINUTES = 10;

    /** Maximum verification attempts before a code is locked. */
    public const OTP_MAX_ATTEMPTS = 5;

    // ── Email verification (OTP) ───────────────────────────────────────────────

    /**
     * Generate, store (hashed) and email a fresh 6-digit verification code.
     *
     * Overrides the framework default (which sends a signed link) so that
     * registration and "resend" both deliver an OTP instead.
     */
    public function sendEmailVerificationNotification(): void
    {
        $otp = $this->generateEmailOtp();

        $this->notify(new VerifyEmailOtp($otp, self::OTP_TTL_MINUTES));
    }

    /**
     * Create a new OTP, persist its hash + expiry, and return the plaintext code.
     */
    public function generateEmailOtp(): string
    {
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $this->forceFill([
            'email_otp'            => Hash::make($otp),
            'email_otp_expires_at' => now()->addMinutes(self::OTP_TTL_MINUTES),
            'email_otp_attempts'   => 0,
        ])->save();

        return $otp;
    }

    /**
     * Validate a submitted OTP. On success the email is marked verified and the
     * code is cleared. Wrong codes increment the attempt counter.
     */
    public function verifyEmailOtp(string $otp): bool
    {
        if (! $this->email_otp || ! $this->email_otp_expires_at) {
            return false;
        }

        if (now()->greaterThan($this->email_otp_expires_at)) {
            return false;
        }

        if ($this->email_otp_attempts >= self::OTP_MAX_ATTEMPTS) {
            return false;
        }

        if (! Hash::check($otp, $this->email_otp)) {
            $this->increment('email_otp_attempts');
            return false;
        }

        $this->forceFill([
            'email_otp'            => null,
            'email_otp_expires_at' => null,
            'email_otp_attempts'   => 0,
        ])->save();

        if (! $this->hasVerifiedEmail()) {
            $this->markEmailAsVerified();
        }

        return true;
    }

    // ── Role helpers ─────────────────────────────────────────────────────────

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Assign the admin role if this user's email matches ADMIN_EMAIL.
     * Saves silently — safe to call multiple times.
     */
    public function syncAdminRole(): void
    {
        $adminEmail = strtolower(config('app.admin_email', ''));
        if (! $adminEmail) {
            return;
        }

        $shouldBeAdmin = strtolower($this->email) === $adminEmail;
        $correctRole   = $shouldBeAdmin ? 'admin' : 'customer';

        if ($this->role !== $correctRole) {
            // Bypass $fillable by updating the column directly
            $this->newQuery()->where('id', $this->id)->update(['role' => $correctRole]);
            $this->role = $correctRole;
        }
    }

    // ── Relations ─────────────────────────────────────────────────────────────

    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function wishlist(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(PushSubscription::class);
    }
}

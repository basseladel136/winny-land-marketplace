<?php

namespace App\Models;

use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, MustVerifyEmailTrait, Notifiable;

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
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
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

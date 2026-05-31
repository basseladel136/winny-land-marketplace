<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    protected function configureRateLimiting(): void
    {
        // ── Login: 5 attempts per 15 minutes per IP (brute-force protection) ───
        // ThrottleRequestsException (HTTP 429) is caught by bootstrap/app.php
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinutes(15, 5)->by($request->ip());
        });

        // ── Register: 5 registrations per minute per IP ──────────────────────────
        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // ── Email verification resend: 3 per 5 minutes per user/IP ──────────────
        RateLimiter::for('verification-resend', function (Request $request) {
            return Limit::perMinutes(5, 3)
                ->by(optional($request->user())->id ?: $request->ip());
        });

        // ── Public endpoints: 60 per minute per IP ───────────────────────────────
        RateLimiter::for('public', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        // ── Standard API: 120 per minute per user/IP ─────────────────────────────
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(120)->by(
                optional($request->user())->id ?: $request->ip()
            );
        });

        // ── Admin panel: 300 per minute (trusted users, but still limited) ───────
        RateLimiter::for('admin', function (Request $request) {
            return Limit::perMinute(300)->by(
                optional($request->user())->id ?: $request->ip()
            );
        });
    }
}

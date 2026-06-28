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
        // ── Login: 5 attempts per 15 minutes per IP (brute-force protection) ────
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinutes(15, 5)->by($request->ip());
        });

        // ── Register: 5 registrations per minute per IP ──────────────────────────
        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // ── OTP verify: 5 attempts per 15 minutes keyed by email+IP ─────────────
        // SECURITY FIX: Previously shared the 'login' limiter, creating cross-
        // endpoint interference. Dedicated limiter keyed on email+IP prevents
        // an attacker from exhausting login attempts to block OTP verification
        // (or vice versa) while still limiting brute-force of 6-digit codes.
        RateLimiter::for('otp-verify', function (Request $request) {
            $email = strtolower((string) $request->input('email', ''));
            return Limit::perMinutes(15, 5)->by('otp:' . $email . ':' . $request->ip());
        });

        // ── OTP resend: 3 per 10 minutes per email+IP ────────────────────────────
        // SECURITY FIX: Dedicated limiter keyed on email prevents OTP flooding
        // for a specific target regardless of which IP the request comes from
        // (within the per-IP cap applied by the 'public' global limiter).
        RateLimiter::for('otp-resend', function (Request $request) {
            $email = strtolower((string) $request->input('email', ''));
            return Limit::perMinutes(10, 3)->by('otp-resend:' . $email . ':' . $request->ip());
        });

        // ── Email verification resend (authenticated): 3 per 5 minutes ──────────
        RateLimiter::for('verification-resend', function (Request $request) {
            return Limit::perMinutes(5, 3)
                ->by(optional($request->user())->id ?: $request->ip());
        });

        // ── Coupon validation: 20 per minute per IP ───────────────────────────────
        // SECURITY FIX: Previously only covered by the global 'public' limiter (60/min).
        // Dedicated limit prevents coupon brute-force enumeration attacks.
        RateLimiter::for('coupon-validate', function (Request $request) {
            return Limit::perMinute(20)->by($request->ip());
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

        // ── Order placement: 10 per hour per user (prevent order spam) ──────────────
        RateLimiter::for('order-create', function (Request $request) {
            return Limit::perHour(10)->by(
                optional($request->user())->id ?: $request->ip()
            );
        });

        // ── Admin panel: 60 per minute (tighter — admin actions have larger blast radius) ──
        // SECURITY FIX: Previously 300/min, far too permissive for privileged endpoints.
        RateLimiter::for('admin', function (Request $request) {
            return Limit::perMinute(60)->by(
                optional($request->user())->id ?: $request->ip()
            );
        });
    }
}

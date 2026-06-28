<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds security-hardening HTTP response headers to every API response.
 *
 * Each header is chosen for defence-in-depth and is safe to apply to JSON APIs.
 * Adjust CSP as the frontend / payment iframe requirements evolve.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Block MIME-type sniffing — prevents browsers from guessing content types
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Forbid iframe embedding — mitigates clickjacking
        $response->headers->set('X-Frame-Options', 'DENY');

        // Force HTTPS for 1 year; include subdomains
        // Only sent over HTTPS to avoid breaking local HTTP dev
        if ($request->secure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        // Restrict referrer information leakage
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Disable dangerous browser features (camera, mic, geolocation) for the API origin
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), payment=()'
        );

        // Content-Security-Policy — restrictive default for JSON API responses
        // This does not affect the SPA; the SPA serves its own CSP via its server.
        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'none'; frame-ancestors 'none'"
        );

        // Remove the "X-Powered-By: PHP" fingerprinting header added by PHP
        $response->headers->remove('X-Powered-By');

        // Remove server fingerprinting header if set by upstream proxy
        $response->headers->remove('Server');

        return $response;
    }
}

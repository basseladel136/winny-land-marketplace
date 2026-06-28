<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // SECURITY FIX: Previously contained a duplicate 'http://localhost:5173'
    // because it was also the default for FRONTEND_URL.  Use a filter to
    // deduplicate at runtime so the array never contains two identical origins.
    'allowed_origins' => array_values(array_unique(array_filter([
        'http://localhost:5173',
        'http://localhost:3000',
        env('FRONTEND_URL'),
    ]))),

    'allowed_origins_patterns' => [],

    // Restrict to only the headers our API actually needs.
    'allowed_headers' => ['Content-Type', 'Authorization', 'Accept', 'Accept-Language', 'X-Requested-With'],

    // Expose rate-limit headers so the frontend can show helpful messages.
    'exposed_headers' => ['X-RateLimit-Limit', 'X-RateLimit-Remaining', 'Retry-After'],

    // Cache preflight response for 2 hours — eliminates duplicate OPTIONS requests.
    // PERFORMANCE FIX: was 0, causing a preflight round-trip before every request.
    'max_age' => 7200,

    'supports_credentials' => false,

];

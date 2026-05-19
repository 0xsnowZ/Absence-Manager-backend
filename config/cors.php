<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:5173',
        'http://127.0.0.1:5173',
        // Set FRONTEND_URL in Railway dashboard → e.g. https://absence-app-one.vercel.app
        env('FRONTEND_URL', ''),
    ],

    // All patterns must use PCRE delimiters (e.g. #...#) — required by preg_match().
    'allowed_origins_patterns' => [
        // Any *.vercel.app subdomain (covers preview deployments)
        '#^https://[a-zA-Z0-9\-]+\.vercel\.app$#',
        // Render deploy URLs
        '#^https://absence-manager-frontend\.onrender\.com$#',
        '#^https://absence-manager-frontend-[a-zA-Z0-9\-]+\.onrender\.com$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    // Cache preflight for 24 h to reduce OPTIONS round-trips
    'max_age' => 86400,

    // false: we use Bearer token auth (Sanctum token in localStorage).
    // Setting this true forces the browser to include credentials on every
    // preflight and requires an exact origin echo — incompatible with patterns.
    'supports_credentials' => false,

];


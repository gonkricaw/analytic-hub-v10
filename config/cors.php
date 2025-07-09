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
        // Analytics Hub specific origins
        env('APP_URL', 'http://localhost'),
        // Power BI domains
        'https://app.powerbi.com',
        'https://msit.powerbi.com',
        'https://powerbi.microsoft.com',
        // Tableau domains
        'https://public.tableau.com',
        'https://online.tableau.com',
        'https://*.tableauusercontent.com',
        // Google Data Studio domains
        'https://datastudio.google.com',
        'https://lookerstudio.google.com',
        // Additional trusted domains can be added via environment
        ...explode(',', env('CORS_ALLOWED_ORIGINS', '')),
    ],

    'allowed_origins_patterns' => [
        // Allow subdomains for analytics platforms
        '/^https:\/\/[a-zA-Z0-9-]+\.powerbi\.com$/',
        '/^https:\/\/[a-zA-Z0-9-]+\.tableau\.com$/',
        '/^https:\/\/[a-zA-Z0-9-]+\.google\.com$/',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
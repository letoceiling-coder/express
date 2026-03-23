<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS)
    |--------------------------------------------------------------------------
    |
    | Базовые настройки для API. Для партнёрских приложений с секретным
    | токеном см. middleware IntegrationPartnerCors и API_INTEGRATION_TOKEN.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => (static function (): array {
        $raw = env('CORS_ALLOWED_ORIGINS', '*');
        if ($raw === null || $raw === '') {
            return ['*'];
        }
        $parts = array_map('trim', explode(',', (string) $raw));
        $parts = array_values(array_filter($parts, static fn (string $v): bool => $v !== ''));

        return $parts !== [] ? $parts : ['*'];
    })(),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => filter_var(
        env('CORS_SUPPORTS_CREDENTIALS', false),
        FILTER_VALIDATE_BOOLEAN
    ),

];

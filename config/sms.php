<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SMS Auth Settings
    |--------------------------------------------------------------------------
    */

    'code_length' => (int) env('SMS_CODE_LENGTH', 6),
    'code_ttl_minutes' => (int) env('SMS_CODE_TTL_MINUTES', 5),
    'max_attempts' => (int) env('SMS_MAX_ATTEMPTS', 5),

    /*
    |--------------------------------------------------------------------------
    | Rate Limits
    |--------------------------------------------------------------------------
    */

    'rate_limit_per_ip' => (int) env('SMS_RATE_LIMIT_PER_IP', 3),
    'rate_limit_per_ip_decay' => (int) env('SMS_RATE_LIMIT_PER_IP_DECAY', 60),
    'rate_limit_per_phone' => (int) env('SMS_RATE_LIMIT_PER_PHONE', 3),
    'rate_limit_per_phone_decay' => (int) env('SMS_RATE_LIMIT_PER_PHONE_DECAY', 300),

    // Global limit per phone (across all IPs): max 5 SMS per 10 min
    'rate_limit_global_per_phone' => (int) env('SMS_RATE_LIMIT_GLOBAL_PER_PHONE', 5),
    'rate_limit_global_per_phone_decay' => (int) env('SMS_RATE_LIMIT_GLOBAL_PER_PHONE_DECAY', 600),

    // Verify attempts: max 5 per minute per phone
    'verify_rate_limit_per_phone' => (int) env('SMS_VERIFY_RATE_LIMIT_PER_PHONE', 5),
    'verify_rate_limit_decay' => (int) env('SMS_VERIFY_RATE_LIMIT_DECAY', 60),

    // Brute-force delay on verify (seconds)
    'verify_delay_seconds' => (float) env('SMS_VERIFY_DELAY_SECONDS', 1),

    /*
    |--------------------------------------------------------------------------
    | Abuse Detection
    |--------------------------------------------------------------------------
    */

    'abuse_failed_attempts_phone' => (int) env('SMS_ABUSE_FAILED_ATTEMPTS_PHONE', 10),
    'abuse_block_phone_minutes' => (int) env('SMS_ABUSE_BLOCK_PHONE_MINUTES', 60),
    'abuse_failed_attempts_ip' => (int) env('SMS_ABUSE_FAILED_ATTEMPTS_IP', 15),
    'abuse_block_ip_minutes' => (int) env('SMS_ABUSE_BLOCK_IP_MINUTES', 60),

    /*
    |--------------------------------------------------------------------------
    | Cleanup
    |--------------------------------------------------------------------------
    */

    'cleanup_used_older_than_hours' => (int) env('SMS_CLEANUP_USED_OLDER_THAN_HOURS', 24),

    /*
    |--------------------------------------------------------------------------
    | Token
    |--------------------------------------------------------------------------
    */

    'token_expiration_days' => (int) env('SMS_TOKEN_EXPIRATION_DAYS', 7),
    'max_tokens_per_user' => (int) env('SMS_MAX_TOKENS_PER_USER', 3),

];

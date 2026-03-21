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

];

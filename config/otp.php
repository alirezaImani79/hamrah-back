<?php

return [

    /*
    |--------------------------------------------------------------------------
    | One-Time Password Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for the phone-number login codes issued during the OTP
    | authentication flow.
    |
    */

    // Number of digits in a generated code.
    'length' => (int) env('OTP_LENGTH', 6),

    // Seconds a code remains valid after it is issued.
    'ttl' => (int) env('OTP_TTL', 300),

    // Minimum seconds between code requests for the same phone number.
    'throttle_seconds' => (int) env('OTP_THROTTLE_SECONDS', 60),

    // Maximum verification attempts allowed against a single code.
    'max_attempts' => (int) env('OTP_MAX_ATTEMPTS', 5),

];

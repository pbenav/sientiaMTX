<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Email Validation Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration file controls the email validation behavior for
    | appointment bookings. You can enable/disable email verification,
    | set timeouts, and configure which providers to trust.
    |
    */

    'enabled' => env('EMAIL_VALIDATION_ENABLED', true),

    'timeout' => env('EMAIL_VALIDATION_TIMEOUT', 8),

    'trusted_providers' => [
        'gmail.com',
        'googlemail.com',
        'outlook.com',
        'hotmail.com',
        'live.com',
        'msn.com',
        'yahoo.com',
        'yahoo.co.uk',
        'yahoo.es',
        'yahoo.fr',
        'aol.com',
        'icloud.com',
        'me.com',
        'mac.com',
        'protonmail.com',
        'proton.me',
        'pm.me',
        'zoho.com',
        'mail.com',
        'gmx.com',
        'gmx.net',
        'yandex.com',
        'fastmail.com',
        'hey.com',
    ],

    'log_validations' => env('EMAIL_VALIDATION_LOG', true),
];

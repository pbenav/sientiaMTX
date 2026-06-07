<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Custom System Settings
    |--------------------------------------------------------------------------
    |
    | Defined by Sientia system to decouple environment variables from
    | business logic controllers enabling robust application-level caching.
    |
    */

    'default_disk_quota' => (int) env('DEFAULT_DISK_QUOTA', 100),
    
    'kanban_completed_limit' => (int) env('KANBAN_COMPLETED_LIMIT', 10),

    /*
    |--------------------------------------------------------------------------
    | Demo / Privacy Mode
    |--------------------------------------------------------------------------
    |
    | When set to 'on', all sensitive data (names, emails, phones, tokens,
    | chat messages, etc.) will be masked or scrambled in the UI to allow
    | safe demonstrations without exposing real user data.
    |
    */
    'demo_mode' => env('APP_DEMO_MODE', 'off'),

];


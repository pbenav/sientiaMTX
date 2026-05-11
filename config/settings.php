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

];

<?php

return [
    'url' => env('ONLYOFFICE_URL', 'https://office.sientia.com/'),
    'secret' => env('ONLYOFFICE_SECRET', null),
    
    // Extensions that onlyoffice handles
    'extensions' => [
        'word' => ['docx', 'doc', 'odt', 'rtf', 'txt'],
        'cell' => ['xlsx', 'xls', 'ods', 'csv'],
        'slide' => ['pptx', 'ppt', 'odp'],
    ],
    // Configuración para comunicaciones internas directas (LAN)
    'internal_app_url' => env('ONLYOFFICE_INTERNAL_APP_URL'),
    'internal_server_url' => env('ONLYOFFICE_INTERNAL_SERVER_URL'),
];

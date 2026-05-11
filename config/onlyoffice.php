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
];

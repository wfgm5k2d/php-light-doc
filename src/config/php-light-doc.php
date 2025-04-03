<?php

return [
    'path' => [
        'file-documentation' => env('PATH_TO_FILE_DOCUMENTATION') // Указывается полный путь от корня до файла
    ],
    'dirs' => [
        'scan' => env('PATH_TO_DIR_SCAN', 'app/Http/Controllers'), // Пример для множества папок: 'app/Domain, app/Http/Controllers'
        'exclude' => env('PATH_TO_DIR_EXCLUDE', ''), // Пример для множества папок: 'app/Http/Controllers/Admin, CustomBackupController'
    ]
];

<?php


return [
    'table_storage' => [
        'table_name' => 'migrations',
    ],
    'migrations_paths' => [
        'App\DB\Migrations' => 'src\DB\Migrations',
    ],
    'all_or_nothing' => true,
    'check_database_platform' => true,
];

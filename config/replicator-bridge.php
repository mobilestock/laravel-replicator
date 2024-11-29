<?php

return [
    'driver' => 'mysql',
    'url' => env('DB_URL'),
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '3306'),
    'database' => env('DB_DATABASE', 'laravel'),
    'username' => env('DB_REPLICATOR_USERNAME', 'root'),
    'password' => env('DB_REPLICATOR_PASSWORD', ''),
    'unix_socket' => env('REPLICATOR_DB_SOCKET', ''),
    'charset' => env('REPLICATOR_DB_CHARSET', 'utf8mb4'),
    'collation' => env('REPLICATOR_DB_COLLATION', 'utf8mb4_unicode_ci'),
    'prefix' => '',
    'prefix_indexes' => true,
    'strict' => true,
    'engine' => null,
    'options' => extension_loaded('pdo_mysql')
        ? array_filter([
            PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ])
        : [],
];

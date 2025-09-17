<?php

use Illuminate\Support\Str;

return [
    'default' => env('DB_CONNECTION', 'mysql'), // Change to mysql

    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        // 'sandbox' => [
        //     'driver' => 'mysql',
        //     'host' => env('SANDBOX_DB_HOST', env('DB_HOST', '127.0.0.1')),
        //     'port' => env('SANDBOX_DB_PORT', env('DB_PORT', '3306')),
        //     'database' => '', // Will be set dynamically
        //     'username' => env('SANDBOX_DB_USERNAME', env('DB_USERNAME', 'root')),
        //     'password' => env('SANDBOX_DB_PASSWORD', env('DB_PASSWORD', '')),
        //     'unix_socket' => env('SANDBOX_DB_SOCKET', env('DB_SOCKET', '')),
        //     'charset' => 'utf8mb4',
        //     'collation' => 'utf8mb4_unicode_ci',
        //     'prefix' => '',
        //     'prefix_indexes' => true,
        //     'strict' => true,
        //     'engine' => null,
        //     'options' => extension_loaded('pdo_mysql') ? array_filter([
        //         PDO::MYSQL_ATTR_SSL_CA => env('SANDBOX_MYSQL_ATTR_SSL_CA'),
        //     ]) : [],
        // ]
    ],

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],
];

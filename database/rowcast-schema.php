<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use AsceticSoft\Wirebox\Env\EnvResolver;

return static function (string $projectDir): array {
    $env = new EnvResolver($projectDir);

    $driver = $env->get('DB_DRIVER') ?? 'mysql';
    $host = $env->get('DB_HOST') ?? '127.0.0.1';
    $port = $env->get('DB_PORT') ?? '3306';
    $name = $env->get('DB_NAME') ?? 'backend_demo';
    $user = $env->get('DB_USER') ?? 'backend_demo';
    $password = $env->get('DB_PASSWORD') ?? 'backend_demo';

    return [
        'connection' => [
            'dsn' => \sprintf('%s:host=%s;port=%s;dbname=%s;charset=utf8mb4', $driver, $host, $port, $name),
            'username' => $user,
            'password' => $password,
        ],
        'schema' => __DIR__ . '/schema.php',
        'migrations' => __DIR__ . '/migrations',
        'migration_table' => '_rowcast_migrations',
    ];
};

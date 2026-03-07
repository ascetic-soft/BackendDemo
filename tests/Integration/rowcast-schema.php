<?php

declare(strict_types=1);

return static function (string $projectDir): array {
    $sqlitePath = \getenv('TEST_SQLITE_PATH');
    if (!\is_string($sqlitePath) || $sqlitePath === '') {
        throw new \RuntimeException('Environment variable TEST_SQLITE_PATH must be set for integration tests.');
    }

    $migrationsPath = \getenv('TEST_MIGRATIONS_PATH');
    if (!\is_string($migrationsPath) || $migrationsPath === '') {
        throw new \RuntimeException('Environment variable TEST_MIGRATIONS_PATH must be set for integration tests.');
    }

    return [
        'connection' => [
            'dsn' => 'sqlite:' . $sqlitePath,
        ],
        'schema' => $projectDir . '/database/schema.php',
        'migrations' => $migrationsPath,
        'migration_table' => '_rowcast_migrations',
    ];
};

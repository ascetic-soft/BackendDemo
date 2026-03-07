<?php

declare(strict_types=1);

namespace Tests\Integration;

use AsceticSoft\Psr7\ServerRequest;
use AsceticSoft\Rowcast\Connection;
use AsceticSoft\RowcastSchema\Cli\Application as RowcastSchemaApplication;
use AsceticSoft\Waypoint\Router;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

/**
 * Base class for integration tests.
 *
 * Boots the full application stack (DI container, router, middleware)
 * with an in-memory SQLite database instead of MySQL.
 */
abstract class IntegrationTestCase extends TestCase
{
    protected Router $router;
    protected Connection $connection;
    private string $testRuntimeDir;

    protected function setUp(): void
    {
        parent::setUp();

        $projectDir = \dirname(__DIR__, 2);
        $this->testRuntimeDir = $this->createRuntimeDirectory();
        $sqlitePath = $this->testRuntimeDir . '/integration.sqlite';
        $migrationsPath = $this->testRuntimeDir . '/migrations';
        $rowcastConfigPath = $projectDir . '/tests/Integration/rowcast-schema.php';

        $kernel = new TestKernel($projectDir, $sqlitePath);
        $container = $kernel->boot();

        /** @var Connection $connection */
        $connection = $container->get(Connection::class);
        $this->connection = $connection;

        $this->createSchemaViaMigrations(
            rowcastConfigPath: $rowcastConfigPath,
            migrationsPath: $migrationsPath,
            sqlitePath: $sqlitePath,
        );

        $this->router = $kernel->getRouter();
    }

    /**
     * Generates and applies schema migrations for the test SQLite database.
     */
    private function createSchemaViaMigrations(
        string $rowcastConfigPath,
        string $migrationsPath,
        string $sqlitePath,
    ): void {
        \putenv('TEST_SQLITE_PATH=' . $sqlitePath);
        \putenv('TEST_MIGRATIONS_PATH=' . $migrationsPath);

        $cli = new RowcastSchemaApplication();
        $diffCode = $this->runSchemaCli($cli, ['rowcast-schema', '--config=' . $rowcastConfigPath, 'diff']);
        if ($diffCode !== 0) {
            throw new RuntimeException('RowcastSchema diff failed for integration test setup.');
        }

        $migrateCode = $this->runSchemaCli($cli, ['rowcast-schema', '--config=' . $rowcastConfigPath, 'migrate']);
        if ($migrateCode !== 0) {
            throw new RuntimeException('RowcastSchema migrate failed for integration test setup.');
        }
    }

    protected function tearDown(): void
    {
        \putenv('TEST_SQLITE_PATH');
        \putenv('TEST_MIGRATIONS_PATH');
        $this->removeDirectory($this->testRuntimeDir);
        parent::tearDown();
    }

    private function createRuntimeDirectory(): string
    {
        $path = \sys_get_temp_dir() . '/backend-demo-it-' . \bin2hex(\random_bytes(8));
        \mkdir($path, 0o777, true);

        return $path;
    }

    private function removeDirectory(string $path): void
    {
        if (!\is_dir($path)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        /** @var SplFileInfo $entry */
        foreach ($iterator as $entry) {
            if ($entry->isDir()) {
                \rmdir($entry->getPathname());
            } else {
                \unlink($entry->getPathname());
            }
        }

        \rmdir($path);
    }

    /**
     * @param list<string> $argv
     */
    private function runSchemaCli(RowcastSchemaApplication $application, array $argv): int
    {
        \ob_start();

        try {
            return $application->run($argv);
        } finally {
            \ob_end_clean();
        }
    }

    // ------------------------------------------------------------------
    // HTTP helpers
    // ------------------------------------------------------------------

    /**
     * Send a GET request through the router.
     *
     * @param array<string, string> $queryParams
     */
    protected function get(string $uri, array $queryParams = []): ResponseInterface
    {
        $request = new ServerRequest('GET', $uri);

        if ($queryParams !== []) {
            $request = $request->withQueryParams($queryParams);
        }

        return $this->router->handle($request);
    }

    /**
     * Send a POST request with a JSON body through the router.
     *
     * @param array<string, mixed> $body
     */
    protected function post(string $uri, array $body = []): ResponseInterface
    {
        $request = new ServerRequest('POST', $uri)
            ->withHeader('Content-Type', 'application/json')
            ->withParsedBody($body);

        return $this->router->handle($request);
    }

    /**
     * Send a PUT request with a JSON body through the router.
     *
     * @param array<string, mixed> $body
     */
    protected function put(string $uri, array $body = []): ResponseInterface
    {
        $request = new ServerRequest('PUT', $uri)
            ->withHeader('Content-Type', 'application/json')
            ->withParsedBody($body);

        return $this->router->handle($request);
    }

    /**
     * Decode JSON response body.
     *
     * @return array<string, mixed>
     */
    protected function json(ResponseInterface $response): array
    {
        $body = (string) $response->getBody();

        /** @var array<string, mixed> */
        return \json_decode($body, true, 512, JSON_THROW_ON_ERROR);
    }
}

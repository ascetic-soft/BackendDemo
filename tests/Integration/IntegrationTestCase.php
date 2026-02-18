<?php

declare(strict_types=1);

namespace Tests\Integration;

use AsceticSoft\Psr7\ServerRequest;
use AsceticSoft\Rowcast\Connection;
use AsceticSoft\Waypoint\Router;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

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

    protected function setUp(): void
    {
        parent::setUp();

        $projectDir = \dirname(__DIR__, 2);

        $kernel = new TestKernel($projectDir);
        $container = $kernel->boot();

        /** @var Connection $connection */
        $connection = $container->get(Connection::class);
        $this->connection = $connection;

        $this->createSchema();

        $this->router = $kernel->getRouter();
    }

    /**
     * Create database tables using SQLite-compatible schema.
     */
    private function createSchema(): void
    {
        $this->connection->executeStatement('
            CREATE TABLE IF NOT EXISTS products (
                id             TEXT    NOT NULL PRIMARY KEY,
                name           TEXT    NOT NULL,
                price_amount   INTEGER NOT NULL DEFAULT 0,
                price_currency TEXT    NOT NULL DEFAULT \'USD\',
                description    TEXT    NOT NULL DEFAULT \'\',
                created_at     TEXT    NOT NULL DEFAULT (datetime(\'now\')),
                updated_at     TEXT    NOT NULL DEFAULT (datetime(\'now\'))
            )
        ');

        $this->connection->executeStatement('
            CREATE TABLE IF NOT EXISTS orders (
                id             TEXT    NOT NULL PRIMARY KEY,
                status         TEXT    NOT NULL DEFAULT \'pending\',
                customer_name  TEXT    NOT NULL,
                total_amount   INTEGER NOT NULL DEFAULT 0,
                total_currency TEXT    NOT NULL DEFAULT \'USD\',
                created_at     TEXT    NOT NULL DEFAULT (datetime(\'now\')),
                updated_at     TEXT    NOT NULL DEFAULT (datetime(\'now\'))
            )
        ');

        $this->connection->executeStatement('
            CREATE TABLE IF NOT EXISTS order_lines (
                order_id            TEXT    NOT NULL,
                position            INTEGER NOT NULL DEFAULT 0,
                product_id          TEXT    NOT NULL,
                product_name        TEXT    NOT NULL,
                unit_price_amount   INTEGER NOT NULL DEFAULT 0,
                unit_price_currency TEXT    NOT NULL DEFAULT \'USD\',
                quantity            INTEGER NOT NULL DEFAULT 1,
                PRIMARY KEY (order_id, position),
                FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
            )
        ');
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

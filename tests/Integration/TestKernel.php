<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Http\Middleware\ErrorHandlerMiddleware;
use App\Http\Middleware\JsonResponseMiddleware;
use AsceticSoft\Rowcast\Connection;
use AsceticSoft\Waypoint\Router;
use AsceticSoft\Waypoint\RouteRegistrar;
use AsceticSoft\Wirebox\Container;
use AsceticSoft\Wirebox\ContainerBuilder;
use Core\SharedKernel\CQRS\AsCommandHandler;
use Core\SharedKernel\CQRS\AsQueryHandler;
use Core\SharedKernel\CQRS\CommandInterface;
use Core\SharedKernel\CQRS\QueryInterface;
use Psr\Http\Server\MiddlewareInterface;

/**
 * Test-specific Kernel that uses SQLite in-memory instead of MySQL.
 *
 * Replicates the production Kernel's setup but overrides
 * the database connection for integration testing.
 */
final class TestKernel
{
    private ?Container $container = null;
    private ?Router $router = null;

    public function __construct(
        private readonly string $projectDir,
    ) {}

    public function boot(): Container
    {
        if ($this->container !== null) {
            return $this->container;
        }

        $builder = new ContainerBuilder(projectDir: $this->projectDir);

        // Interfaces with multiple implementations — exclude from auto-binding
        // to avoid ambiguous auto-binding errors during scanning.
        $builder->excludeFromAutoBinding(
            MiddlewareInterface::class,
            CommandInterface::class,
            QueryInterface::class,
        );

        // CQRS: tag handlers by their domain attributes
        $builder->registerForAutoconfiguration(AsCommandHandler::class)->tag('command.handler');
        $builder->registerForAutoconfiguration(AsQueryHandler::class)->tag('query.handler');

        // Scan application services (infrastructure layer)
        $builder->scan($this->projectDir . '/src');

        // Scan domain handlers (core layer)
        $builder->scan($this->projectDir . '/core');

        // Override database connection with SQLite in-memory
        $builder->register(Connection::class, static function (): Connection {
            return Connection::create('sqlite::memory:', nestTransactions: true);
        });

        $this->container = $builder->build();

        return $this->container;
    }

    public function getRouter(): Router
    {
        if ($this->router !== null) {
            return $this->router;
        }

        $container = $this->boot();

        $registrar = new RouteRegistrar();

        $registrar->scanDirectory($this->projectDir . '/src/Http', 'App\\Http');

        $this->router = new Router($container, $registrar->getRouteCollection());

        // Global middleware (same as production Kernel)
        $this->router->addMiddleware(ErrorHandlerMiddleware::class);
        $this->router->addMiddleware(JsonResponseMiddleware::class);

        return $this->router;
    }
}

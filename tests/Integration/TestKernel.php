<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Http\Controller\OrderController;
use App\Http\Controller\ProductController;
use App\Http\Middleware\ErrorHandlerMiddleware;
use App\Http\Middleware\JsonResponseMiddleware;
use AsceticSoft\Rowcast\Connection;
use AsceticSoft\Waypoint\Router;
use AsceticSoft\Wirebox\Container;
use AsceticSoft\Wirebox\ContainerBuilder;
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

        // Same autoconfiguration as production Kernel
        $builder->registerForAutoconfiguration(MiddlewareInterface::class);
        $builder->registerForAutoconfiguration(CommandInterface::class);
        $builder->registerForAutoconfiguration(QueryInterface::class);
        $builder->registerForAutoconfiguration(\Stringable::class);
        $builder->registerForAutoconfiguration(\Throwable::class);

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

        $this->router = new Router($container);

        // Global middleware (same as production Kernel)
        $this->router->addMiddleware(ErrorHandlerMiddleware::class);
        $this->router->addMiddleware(JsonResponseMiddleware::class);

        // Load attribute-based routes from controllers
        $this->router->loadAttributes(
            ProductController::class,
            OrderController::class,
        );

        return $this->router;
    }
}

<?php

declare(strict_types=1);

namespace App;

use App\Http\Controller\OrderController;
use App\Http\Controller\ProductController;
use App\Http\Middleware\ErrorHandlerMiddleware;
use App\Http\Middleware\JsonResponseMiddleware;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use AsceticSoft\Rowcast\Connection;
use AsceticSoft\Waypoint\Router;
use AsceticSoft\Wirebox\Container;
use AsceticSoft\Wirebox\ContainerBuilder;
use Core\Order\Domain\Repository\OrderRepositoryInterface;
use Core\Product\Domain\Repository\ProductRepositoryInterface;
use Core\SharedKernel\CQRS\CommandHandlerInterface;
use Core\SharedKernel\CQRS\QueryHandlerInterface;

final class Kernel
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

        // Exclude persistence DTOs and entities from DI scanning
        $builder->exclude('Repository/OrderRow.php');
        $builder->exclude('Repository/OrderLineRow.php');

        // Autoconfigure CQRS handler tags (keeps core free of infrastructure attributes)
        $builder->registerForAutoconfiguration(CommandHandlerInterface::class)->tag('command.handler');
        $builder->registerForAutoconfiguration(QueryHandlerInterface::class)->tag('query.handler');

        // Scan application services (infrastructure layer)
        $builder->scan($this->projectDir . '/src');

        // Scan domain handlers (core layer)
        $builder->scan($this->projectDir . '/core');

        // Bind repository interfaces to concrete implementations
        $builder->bind(ProductRepositoryInterface::class, ProductRepository::class);
        $builder->bind(OrderRepositoryInterface::class, OrderRepository::class);

        // Database connection via factory
        $builder->register(Connection::class, static function (Container $c): Connection {
            $driver = self::paramString($c, 'DB_DRIVER', 'mysql');
            $host = self::paramString($c, 'DB_HOST', '127.0.0.1');
            $port = self::paramString($c, 'DB_PORT', '3306');
            $name = self::paramString($c, 'DB_NAME', 'backend_demo');
            $user = self::paramString($c, 'DB_USER', 'root');
            $password = self::paramString($c, 'DB_PASSWORD', '');

            $dsn = \sprintf('%s:host=%s;port=%s;dbname=%s;charset=utf8mb4', $driver, $host, $port, $name);

            return Connection::create($dsn, $user, $password, nestTransactions: true);
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

        // Global middleware (FIFO: error handler wraps everything, then JSON headers)
        $this->router->addMiddleware(ErrorHandlerMiddleware::class);
        $this->router->addMiddleware(JsonResponseMiddleware::class);

        // Load attribute-based routes from controllers
        $this->router->loadAttributes(
            ProductController::class,
            OrderController::class,
        );

        return $this->router;
    }

    private static function paramString(Container $container, string $name, string $default): string
    {
        $value = $container->getParameter($name);

        return \is_string($value) ? $value : $default;
    }
}

<?php

declare(strict_types=1);

namespace App\CQRS;

use AsceticSoft\Wirebox\Container;
use Core\SharedKernel\CQRS\AsQueryHandler;
use Core\SharedKernel\CQRS\QueryInterface;
use ReflectionClass;
use RuntimeException;

/**
 * Routes queries to their handlers using Wirebox tagged services.
 *
 * Each handler is resolved via the 'query.handler' tag and matched
 * by the query class declared in the #[AsQueryHandler] attribute.
 */
final class QueryBus
{
    /** @var array<class-string, callable>|null */
    private ?array $handlerMap = null;

    public function __construct(
        private readonly Container $container,
    ) {}

    public function dispatch(QueryInterface $query): mixed
    {
        $handler = $this->resolveHandler($query);

        return ($handler)($query);
    }

    private function resolveHandler(QueryInterface $query): callable
    {
        $map = $this->getHandlerMap();
        $queryClass = $query::class;

        if (!isset($map[$queryClass])) {
            throw new RuntimeException(\sprintf('No handler registered for query "%s".', $queryClass));
        }

        return $map[$queryClass];
    }

    /**
     * @return array<class-string, callable>
     */
    private function getHandlerMap(): array
    {
        if ($this->handlerMap !== null) {
            return $this->handlerMap;
        }

        $this->handlerMap = [];

        foreach ($this->container->getTagged('query.handler') as $handler) {
            $queryClass = self::extractQueryClass($handler);

            if ($queryClass !== null) {
                $this->handlerMap[$queryClass] = $handler;
            }
        }

        return $this->handlerMap;
    }

    /**
     * @return class-string|null
     */
    private static function extractQueryClass(object $handler): ?string
    {
        $ref = new ReflectionClass($handler);
        $attrs = $ref->getAttributes(AsQueryHandler::class);

        if ($attrs === []) {
            return null;
        }

        /** @var AsQueryHandler $attr */
        $attr = $attrs[0]->newInstance();

        return $attr->query;
    }
}

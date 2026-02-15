<?php

declare(strict_types=1);

namespace App\CQRS;

use AsceticSoft\Wirebox\Container;
use Core\SharedKernel\CQRS\QueryHandlerInterface;
use Core\SharedKernel\CQRS\QueryInterface;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use RuntimeException;

/**
 * Routes queries to their handlers using Wirebox tagged services.
 *
 * Each handler is resolved via the 'query.handler' tag and matched
 * by inspecting the __invoke parameter type-hint.
 */
final class QueryBus
{
    /** @var array<class-string, QueryHandlerInterface>|null */
    private ?array $handlerMap = null;

    public function __construct(
        private readonly Container $container,
    ) {}

    public function dispatch(QueryInterface $query): mixed
    {
        $handler = $this->resolveHandler($query);

        return ($handler)($query);
    }

    private function resolveHandler(QueryInterface $query): QueryHandlerInterface
    {
        $map = $this->getHandlerMap();
        $queryClass = $query::class;

        if (!isset($map[$queryClass])) {
            throw new RuntimeException(\sprintf('No handler registered for query "%s".', $queryClass));
        }

        return $map[$queryClass];
    }

    /**
     * @return array<class-string, QueryHandlerInterface>
     */
    private function getHandlerMap(): array
    {
        if ($this->handlerMap !== null) {
            return $this->handlerMap;
        }

        $this->handlerMap = [];

        foreach ($this->container->getTagged('query.handler') as $handler) {
            \assert($handler instanceof QueryHandlerInterface);
            $queryClass = self::extractParameterType($handler);

            if ($queryClass !== null) {
                $this->handlerMap[$queryClass] = $handler;
            }
        }

        return $this->handlerMap;
    }

    /**
     * @return class-string|null
     */
    private static function extractParameterType(QueryHandlerInterface $handler): ?string
    {
        try {
            $ref = new ReflectionMethod($handler, '__invoke');
            $params = $ref->getParameters();

            if ($params === []) {
                return null;
            }

            $type = $params[0]->getType();

            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                /** @var class-string */
                return $type->getName();
            }
        } catch (ReflectionException) {
            // ignore
        }

        return null;
    }
}

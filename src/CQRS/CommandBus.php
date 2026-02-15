<?php

declare(strict_types=1);

namespace App\CQRS;

use AsceticSoft\Wirebox\Container;
use Core\SharedKernel\CQRS\CommandHandlerInterface;
use Core\SharedKernel\CQRS\CommandInterface;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use RuntimeException;

/**
 * Routes commands to their handlers using Wirebox tagged services.
 *
 * Each handler is resolved via the 'command.handler' tag and matched
 * by inspecting the __invoke parameter type-hint.
 */
final class CommandBus
{
    /** @var array<class-string, CommandHandlerInterface>|null */
    private ?array $handlerMap = null;

    public function __construct(
        private readonly Container $container,
    ) {}

    public function dispatch(CommandInterface $command): void
    {
        $handler = $this->resolveHandler($command);
        ($handler)($command);
    }

    private function resolveHandler(CommandInterface $command): CommandHandlerInterface
    {
        $map = $this->getHandlerMap();
        $commandClass = $command::class;

        if (!isset($map[$commandClass])) {
            throw new RuntimeException(\sprintf('No handler registered for command "%s".', $commandClass));
        }

        return $map[$commandClass];
    }

    /**
     * @return array<class-string, CommandHandlerInterface>
     */
    private function getHandlerMap(): array
    {
        if ($this->handlerMap !== null) {
            return $this->handlerMap;
        }

        $this->handlerMap = [];

        foreach ($this->container->getTagged('command.handler') as $handler) {
            \assert($handler instanceof CommandHandlerInterface);
            $commandClass = self::extractParameterType($handler);

            if ($commandClass !== null) {
                $this->handlerMap[$commandClass] = $handler;
            }
        }

        return $this->handlerMap;
    }

    /**
     * @return class-string|null
     */
    private static function extractParameterType(CommandHandlerInterface $handler): ?string
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

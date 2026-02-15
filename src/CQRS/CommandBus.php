<?php

declare(strict_types=1);

namespace App\CQRS;

use AsceticSoft\Wirebox\Container;
use Core\SharedKernel\CQRS\AsCommandHandler;
use Core\SharedKernel\CQRS\CommandInterface;
use ReflectionClass;
use RuntimeException;

/**
 * Routes commands to their handlers using Wirebox tagged services.
 *
 * Each handler is resolved via the 'command.handler' tag and matched
 * by the command class declared in the #[AsCommandHandler] attribute.
 */
final class CommandBus
{
    /** @var array<class-string, callable>|null */
    private ?array $handlerMap = null;

    public function __construct(
        private readonly Container $container,
    ) {}

    public function dispatch(CommandInterface $command): void
    {
        $handler = $this->resolveHandler($command);
        ($handler)($command);
    }

    private function resolveHandler(CommandInterface $command): callable
    {
        $map = $this->getHandlerMap();
        $commandClass = $command::class;

        if (!isset($map[$commandClass])) {
            throw new RuntimeException(\sprintf('No handler registered for command "%s".', $commandClass));
        }

        return $map[$commandClass];
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

        foreach ($this->container->getTagged('command.handler') as $handler) {
            $commandClass = self::extractCommandClass($handler);

            if ($commandClass !== null) {
                $this->handlerMap[$commandClass] = $handler;
            }
        }

        return $this->handlerMap;
    }

    /**
     * @return class-string|null
     */
    private static function extractCommandClass(object $handler): ?string
    {
        $ref = new ReflectionClass($handler);
        $attrs = $ref->getAttributes(AsCommandHandler::class);

        if ($attrs === []) {
            return null;
        }

        /** @var AsCommandHandler $attr */
        $attr = $attrs[0]->newInstance();

        return $attr->command;
    }
}

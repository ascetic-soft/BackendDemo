<?php

declare(strict_types=1);

namespace Core\SharedKernel\CQRS;

/**
 * Interface for command handlers.
 * Each handler processes a specific command and performs a state change.
 */
interface CommandHandlerInterface
{
    public function __invoke(object $command): void;
}

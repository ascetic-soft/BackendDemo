<?php

declare(strict_types=1);

namespace Core\SharedKernel\CQRS;

/**
 * Interface for query handlers.
 * Each handler processes a specific query and returns data.
 */
interface QueryHandlerInterface
{
    public function __invoke(object $query): mixed;
}

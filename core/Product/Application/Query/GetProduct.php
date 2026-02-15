<?php

declare(strict_types=1);

namespace Core\Product\Application\Query;

use Core\SharedKernel\CQRS\QueryInterface;

final readonly class GetProduct implements QueryInterface
{
    public function __construct(
        public string $id,
    ) {}
}

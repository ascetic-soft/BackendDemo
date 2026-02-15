<?php

declare(strict_types=1);

namespace Core\Order\Application\Query;

use Core\SharedKernel\CQRS\QueryInterface;

final readonly class ListOrders implements QueryInterface
{
    public function __construct(
        public int $limit = 50,
        public int $offset = 0,
    ) {}
}

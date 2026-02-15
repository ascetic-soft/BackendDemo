<?php

declare(strict_types=1);

namespace Core\Order\Application\Query;

use Core\SharedKernel\CQRS\QueryInterface;

final readonly class GetOrder implements QueryInterface
{
    public function __construct(
        public string $id,
    ) {}
}

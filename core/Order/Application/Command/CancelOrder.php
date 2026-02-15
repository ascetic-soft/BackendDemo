<?php

declare(strict_types=1);

namespace Core\Order\Application\Command;

use Core\SharedKernel\CQRS\CommandInterface;

final readonly class CancelOrder implements CommandInterface
{
    public function __construct(
        public string $id,
    ) {}
}

<?php

declare(strict_types=1);

namespace Core\Order\Application\Command;

use Core\SharedKernel\CQRS\CommandInterface;

final readonly class PlaceOrder implements CommandInterface
{
    /**
     * @param list<PlaceOrderLine> $lines
     */
    public function __construct(
        public string $customerName,
        public array $lines,
    ) {}
}

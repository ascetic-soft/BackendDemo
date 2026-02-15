<?php

declare(strict_types=1);

namespace Core\Order\Application\Command;

final readonly class PlaceOrderLine
{
    public function __construct(
        public string $productId,
        public int $quantity,
    ) {}
}

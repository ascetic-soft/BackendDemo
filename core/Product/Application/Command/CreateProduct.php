<?php

declare(strict_types=1);

namespace Core\Product\Application\Command;

use Core\SharedKernel\CQRS\CommandInterface;

final readonly class CreateProduct implements CommandInterface
{
    public function __construct(
        public string $name,
        public int $priceAmount,
        public string $priceCurrency,
        public string $description = '',
    ) {}
}

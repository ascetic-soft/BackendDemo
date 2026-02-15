<?php

declare(strict_types=1);

namespace Core\Product\Application\Command;

use Core\SharedKernel\CQRS\CommandInterface;

final readonly class UpdateProduct implements CommandInterface
{
    public function __construct(
        public string $id,
        public ?string $name = null,
        public ?int $priceAmount = null,
        public ?string $priceCurrency = null,
        public ?string $description = null,
    ) {}
}

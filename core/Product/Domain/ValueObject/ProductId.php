<?php

declare(strict_types=1);

namespace Core\Product\Domain\ValueObject;

use Core\SharedKernel\ValueObject\UuidId;

/**
 * Unique identifier for a Product aggregate.
 */
final readonly class ProductId extends UuidId {}

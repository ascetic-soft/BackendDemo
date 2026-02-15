<?php

declare(strict_types=1);

namespace Core\Order\Domain\ValueObject;

use Core\SharedKernel\ValueObject\UuidId;

/**
 * Unique identifier for an Order aggregate.
 */
final readonly class OrderId extends UuidId {}

<?php

declare(strict_types=1);

namespace Core\Order\Domain\Exception;

use Core\Order\Domain\ValueObject\OrderId;
use RuntimeException;

final class OrderNotFoundException extends RuntimeException
{
    public static function withId(OrderId $id): self
    {
        return new self(\sprintf('Order with ID "%s" not found.', $id->value));
    }
}

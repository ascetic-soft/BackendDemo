<?php

declare(strict_types=1);

namespace Core\Product\Domain\Exception;

use Core\Product\Domain\ValueObject\ProductId;
use RuntimeException;

final class ProductNotFoundException extends RuntimeException
{
    public static function withId(ProductId $id): self
    {
        return new self(\sprintf('Product with ID "%s" not found.', $id->value));
    }
}

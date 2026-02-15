<?php

declare(strict_types=1);

namespace Core\Product\Domain\ValueObject;

use InvalidArgumentException;

/**
 * Product name value object with validation.
 */
final readonly class ProductName
{
    public string $value;

    public function __construct(string $value)
    {
        $trimmed = \trim($value);

        if ($trimmed === '') {
            throw new InvalidArgumentException('Product name cannot be empty.');
        }

        if (\mb_strlen($trimmed) > 255) {
            throw new InvalidArgumentException('Product name cannot exceed 255 characters.');
        }

        $this->value = $trimmed;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

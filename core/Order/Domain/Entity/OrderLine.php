<?php

declare(strict_types=1);

namespace Core\Order\Domain\Entity;

use Core\Product\Domain\ValueObject\Money;
use Core\Product\Domain\ValueObject\ProductId;
use InvalidArgumentException;

/**
 * A single line item within an Order.
 */
final readonly class OrderLine
{
    public function __construct(
        private ProductId $productId,
        private string $productName,
        private Money $unitPrice,
        private int $quantity,
    ) {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Order line quantity must be at least 1.');
        }
    }

    public function getProductId(): ProductId
    {
        return $this->productId;
    }

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function getUnitPrice(): Money
    {
        return $this->unitPrice;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getLineTotal(): Money
    {
        return $this->unitPrice->multiply($this->quantity);
    }
}

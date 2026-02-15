<?php

declare(strict_types=1);

namespace Core\Order\Application\DTO;

/**
 * Data Transfer Object for an order line item.
 */
final class OrderLineDTO
{
    public string $productId;
    public string $productName;
    public int $unitPriceAmount;
    public string $unitPriceCurrency;
    public int $quantity;
    public int $lineTotalAmount;
}

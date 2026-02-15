<?php

declare(strict_types=1);

namespace App\Repository;

/**
 * Internal DTO for order_lines table persistence via Rowcast.
 *
 * @internal
 */
final class OrderLineRow
{
    public string $orderId;
    public int $position;
    public string $productId;
    public string $productName;
    public int $unitPriceAmount;
    public string $unitPriceCurrency;
    public int $quantity;
}

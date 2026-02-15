<?php

declare(strict_types=1);

namespace App\Repository;

use DateTimeImmutable;

/**
 * Internal DTO for orders table persistence via Rowcast.
 *
 * @internal
 */
final class OrderRow
{
    public string $id;
    public string $status;
    public string $customerName;
    public int $totalAmount;
    public string $totalCurrency;
    public DateTimeImmutable $createdAt;
    public DateTimeImmutable $updatedAt;
}

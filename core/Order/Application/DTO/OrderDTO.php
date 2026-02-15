<?php

declare(strict_types=1);

namespace Core\Order\Application\DTO;

use DateTimeImmutable;

/**
 * Data Transfer Object for Order read operations.
 */
final class OrderDTO
{
    public string $id;
    public string $status;
    public string $customerName;
    public int $totalAmount;
    public string $totalCurrency;
    /** @var list<OrderLineDTO> */
    public array $lines = [];
    public DateTimeImmutable $createdAt;
    public DateTimeImmutable $updatedAt;
}

<?php

declare(strict_types=1);

namespace Core\Order\Domain\Repository;

use Core\Order\Domain\Entity\Order;
use Core\Order\Domain\ValueObject\OrderId;

/**
 * Repository interface for Order aggregate persistence.
 */
interface OrderRepositoryInterface
{
    public function findById(OrderId $id): ?Order;

    /**
     * @return list<Order>
     */
    public function findAll(int $limit = 50, int $offset = 0): array;

    public function save(Order $order): void;

    public function delete(OrderId $id): void;
}

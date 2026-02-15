<?php

declare(strict_types=1);

namespace App\Repository;

use AsceticSoft\Rowcast\Connection;
use Core\Order\Domain\Entity\Order;
use Core\Order\Domain\Entity\OrderLine;
use Core\Order\Domain\Repository\OrderRepositoryInterface;
use Core\Order\Domain\ValueObject\OrderId;
use Core\Order\Domain\ValueObject\OrderStatus;
use Core\Product\Domain\ValueObject\Money;
use Core\Product\Domain\ValueObject\ProductId;
use DateTimeImmutable;

final readonly class OrderRepository implements OrderRepositoryInterface
{
    public function __construct(
        private Connection $connection,
    ) {}

    public function findById(OrderId $id): ?Order
    {
        /** @var array<string, mixed>|false $row */
        $row = $this->connection->fetchAssociative(
            'SELECT * FROM orders WHERE id = ?',
            [$id->value],
        );

        if ($row === false) {
            return null;
        }

        return $this->hydrateOrder($row);
    }

    /**
     * @return list<Order>
     */
    public function findAll(int $limit = 50, int $offset = 0): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT * FROM orders ORDER BY created_at DESC LIMIT ? OFFSET ?',
            [$limit, $offset],
        );

        return \array_map($this->hydrateOrder(...), $rows);
    }

    public function save(Order $order): void
    {
        $this->connection->transactional(function (Connection $conn) use ($order): void {
            $existing = $conn->fetchAssociative(
                'SELECT id FROM orders WHERE id = ?',
                [$order->getId()->value],
            );

            if ($existing === false) {
                $this->insertOrder($order);
            } else {
                $this->updateOrder($order);
            }
        });
    }

    public function delete(OrderId $id): void
    {
        $this->connection->transactional(function () use ($id): void {
            $this->connection->executeStatement(
                'DELETE FROM order_lines WHERE order_id = ?',
                [$id->value],
            );
            $this->connection->executeStatement(
                'DELETE FROM orders WHERE id = ?',
                [$id->value],
            );
        });
    }

    private function insertOrder(Order $order): void
    {
        $total = $order->getTotal();

        $this->connection->executeStatement(
            'INSERT INTO orders (id, status, customer_name, total_amount, total_currency, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $order->getId()->value,
                $order->getStatus()->value,
                $order->getCustomerName(),
                $total->amount,
                $total->currency,
                $order->getCreatedAt()->format('Y-m-d H:i:s'),
                $order->getUpdatedAt()->format('Y-m-d H:i:s'),
            ],
        );

        foreach ($order->getLines() as $position => $line) {
            $this->insertOrderLine($order->getId(), $position, $line);
        }
    }

    private function updateOrder(Order $order): void
    {
        $total = $order->getTotal();

        $this->connection->executeStatement(
            'UPDATE orders
             SET status = ?, customer_name = ?, total_amount = ?, total_currency = ?, created_at = ?, updated_at = ?
             WHERE id = ?',
            [
                $order->getStatus()->value,
                $order->getCustomerName(),
                $total->amount,
                $total->currency,
                $order->getCreatedAt()->format('Y-m-d H:i:s'),
                $order->getUpdatedAt()->format('Y-m-d H:i:s'),
                $order->getId()->value,
            ],
        );

        // Replace lines: delete old, insert new
        $this->connection->executeStatement(
            'DELETE FROM order_lines WHERE order_id = ?',
            [$order->getId()->value],
        );

        foreach ($order->getLines() as $position => $line) {
            $this->insertOrderLine($order->getId(), $position, $line);
        }
    }

    private function insertOrderLine(OrderId $orderId, int $position, OrderLine $line): void
    {
        $this->connection->executeStatement(
            'INSERT INTO order_lines (order_id, position, product_id, product_name, unit_price_amount, unit_price_currency, quantity)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $orderId->value,
                $position,
                $line->getProductId()->value,
                $line->getProductName(),
                $line->getUnitPrice()->amount,
                $line->getUnitPrice()->currency,
                $line->getQuantity(),
            ],
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrateOrder(array $row): Order
    {
        /** @var string $id */
        $id = $row['id'];
        $orderId = new OrderId($id);

        /** @var list<array{order_id: string, position: int, product_id: string, product_name: string, unit_price_amount: int, unit_price_currency: string, quantity: int}> $lineRows */
        $lineRows = $this->connection->fetchAllAssociative(
            'SELECT * FROM order_lines WHERE order_id = ? ORDER BY position ASC',
            [$orderId->value],
        );

        $lines = \array_map(static function (array $lineRow): OrderLine {
            return new OrderLine(
                productId: new ProductId($lineRow['product_id']),
                productName: $lineRow['product_name'],
                unitPrice: new Money($lineRow['unit_price_amount'], $lineRow['unit_price_currency']),
                quantity: $lineRow['quantity'],
            );
        }, $lineRows);

        /** @var string $status */
        $status = $row['status'];
        /** @var string $customerName */
        $customerName = $row['customer_name'];
        /** @var string $createdAt */
        $createdAt = $row['created_at'];
        /** @var string $updatedAt */
        $updatedAt = $row['updated_at'];

        return Order::reconstitute(
            id: $orderId,
            status: OrderStatus::from($status),
            customerName: $customerName,
            lines: $lines,
            createdAt: new DateTimeImmutable($createdAt),
            updatedAt: new DateTimeImmutable($updatedAt),
        );
    }
}

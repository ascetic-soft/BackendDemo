<?php

declare(strict_types=1);

namespace App\Repository;

use AsceticSoft\Rowcast\Connection;
use AsceticSoft\Rowcast\DataMapper;
use AsceticSoft\Rowcast\Mapping\ResultSetMapping;
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
    private DataMapper $mapper;

    public function __construct(
        private Connection $connection,
    ) {
        $this->mapper = new DataMapper($this->connection);
    }

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
            $this->mapper->delete(
                $this->createOrderLineRsm(),
                ['order_id' => $id->value],
            );
            $this->mapper->delete(
                $this->createOrderRsm(),
                ['id' => $id->value],
            );
        });
    }

    private function insertOrder(Order $order): void
    {
        $rsm = $this->createOrderRsm();
        $orderData = new OrderRow();
        $orderData->id = $order->getId()->value;
        $orderData->status = $order->getStatus()->value;
        $orderData->customerName = $order->getCustomerName();
        $total = $order->getTotal();
        $orderData->totalAmount = $total->amount;
        $orderData->totalCurrency = $total->currency;
        $orderData->createdAt = $order->getCreatedAt();
        $orderData->updatedAt = $order->getUpdatedAt();

        $this->mapper->insert($rsm, $orderData);

        foreach ($order->getLines() as $index => $line) {
            $this->insertOrderLine($order->getId(), $index, $line);
        }
    }

    private function updateOrder(Order $order): void
    {
        $rsm = $this->createOrderRsm();
        $orderData = new OrderRow();
        $orderData->id = $order->getId()->value;
        $orderData->status = $order->getStatus()->value;
        $orderData->customerName = $order->getCustomerName();
        $total = $order->getTotal();
        $orderData->totalAmount = $total->amount;
        $orderData->totalCurrency = $total->currency;
        $orderData->createdAt = $order->getCreatedAt();
        $orderData->updatedAt = $order->getUpdatedAt();

        $this->mapper->update($rsm, $orderData, ['id' => $order->getId()->value]);

        // Replace lines: delete old, insert new
        $this->mapper->delete($this->createOrderLineRsm(), ['order_id' => $order->getId()->value]);

        foreach ($order->getLines() as $index => $line) {
            $this->insertOrderLine($order->getId(), $index, $line);
        }
    }

    private function insertOrderLine(OrderId $orderId, int $position, OrderLine $line): void
    {
        $rsm = $this->createOrderLineRsm();
        $row = new OrderLineRow();
        $row->orderId = $orderId->value;
        $row->position = $position;
        $row->productId = $line->getProductId()->value;
        $row->productName = $line->getProductName();
        $row->unitPriceAmount = $line->getUnitPrice()->amount;
        $row->unitPriceCurrency = $line->getUnitPrice()->currency;
        $row->quantity = $line->getQuantity();

        $this->mapper->insert($rsm, $row);
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

    private function createOrderRsm(): ResultSetMapping
    {
        $rsm = new ResultSetMapping(OrderRow::class, table: 'orders');
        $rsm->addField('id', 'id')
            ->addField('status', 'status')
            ->addField('customer_name', 'customerName')
            ->addField('total_amount', 'totalAmount')
            ->addField('total_currency', 'totalCurrency')
            ->addField('created_at', 'createdAt')
            ->addField('updated_at', 'updatedAt');

        return $rsm;
    }

    private function createOrderLineRsm(): ResultSetMapping
    {
        $rsm = new ResultSetMapping(OrderLineRow::class, table: 'order_lines');
        $rsm->addField('order_id', 'orderId')
            ->addField('position', 'position')
            ->addField('product_id', 'productId')
            ->addField('product_name', 'productName')
            ->addField('unit_price_amount', 'unitPriceAmount')
            ->addField('unit_price_currency', 'unitPriceCurrency')
            ->addField('quantity', 'quantity');

        return $rsm;
    }
}

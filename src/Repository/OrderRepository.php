<?php

declare(strict_types=1);

namespace App\Repository;

use AsceticSoft\Rowcast\Connection;
use AsceticSoft\Rowcast\DataMapper;
use AsceticSoft\Rowcast\Mapping;
use Core\Order\Application\DTO\OrderDTO;
use Core\Order\Application\DTO\OrderLineDTO;
use Core\Order\Domain\Entity\Order;
use Core\Order\Domain\Entity\OrderLine;
use Core\Order\Domain\Repository\OrderRepositoryInterface;
use Core\Order\Domain\ValueObject\OrderId;
use Core\Order\Domain\ValueObject\OrderStatus;
use Core\Product\Domain\ValueObject\Money;
use Core\Product\Domain\ValueObject\ProductId;

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
        /** @var OrderDTO|null $dto */
        $dto = $this->mapper->findOne($this->createOrderMapping(), ['id' => $id->value]);
        if ($dto === null) {
            return null;
        }

        return $this->toOrderDomain(
            $dto,
            $this->loadLinesByOrderId($dto->id),
        );
    }

    /**
     * @return list<Order>
     */
    public function findAll(int $limit = 50, int $offset = 0): array
    {
        /** @var list<OrderDTO> $orderDtos */
        $orderDtos = $this->mapper->findAll(
            $this->createOrderMapping(),
            orderBy: ['created_at' => 'DESC'],
            limit: $limit,
            offset: $offset,
        );
        if ($orderDtos === []) {
            return [];
        }

        $linesByOrderId = $this->loadLinesForOrders($orderDtos);

        return \array_map(
            fn(OrderDTO $dto): Order => $this->toOrderDomain($dto, $linesByOrderId[$dto->id] ?? []),
            $orderDtos,
        );
    }

    public function save(Order $order): void
    {
        $this->connection->transactional(function () use ($order): void {
            $this->mapper->save(
                $this->createOrderMapping(),
                self::toOrderDto($order),
                'id',
            );
            $this->syncOrderLines($order);
        });
    }

    public function delete(OrderId $id): void
    {
        $this->connection->transactional(function () use ($id): void {
            $this->mapper->delete($this->createOrderLineMapping(), ['order_id' => $id->value]);
            $this->mapper->delete($this->createOrderMapping(), ['id' => $id->value]);
        });
    }

    private function syncOrderLines(Order $order): void
    {
        $positions = [];
        $orderId = $order->getId()->value;

        foreach ($order->getLines() as $position => $line) {
            $this->mapper->upsert(
                $this->createOrderLineMapping(),
                self::toOrderLineDto($orderId, $position, $line),
                'orderId',
                'position',
            );
            $positions[] = $position;
        }

        $this->deleteRemovedOrderLines($orderId, $positions);
    }

    /**
     * @param list<int> $positions
     */
    private function deleteRemovedOrderLines(string $orderId, array $positions): void
    {
        if ($positions === []) {
            $this->mapper->delete($this->createOrderLineMapping(), ['order_id' => $orderId]);

            return;
        }

        $this->connection->createQueryBuilder()
            ->delete('order_lines')
            ->where([
                'order_id' => $orderId,
                'position NOT IN' => $positions,
            ])
            ->executeStatement()
        ;
    }

    /**
     * @param list<OrderDTO> $orderDtos
     * @return array<string, list<OrderLine>>
     */
    private function loadLinesForOrders(array $orderDtos): array
    {
        $orderIds = \array_map(
            static fn(OrderDTO $orderDto): string => $orderDto->id,
            $orderDtos,
        );
        /** @var list<OrderLineDTO> $lineDtos */
        $lineDtos = $this->mapper->findAll(
            $this->createOrderLineMapping(),
            where: ['order_id' => $orderIds],
            orderBy: ['order_id' => 'ASC', 'position' => 'ASC'],
        );

        $linesByOrderId = [];
        foreach ($lineDtos as $lineDto) {
            $linesByOrderId[$lineDto->orderId] ??= [];
            $linesByOrderId[$lineDto->orderId][] = self::toOrderLineFromDto($lineDto);
        }

        return $linesByOrderId;
    }

    /**
     * @return list<OrderLine>
     */
    private function loadLinesByOrderId(string $orderId): array
    {
        /** @var list<OrderLineDTO> $lineDtos */
        $lineDtos = $this->mapper->findAll(
            $this->createOrderLineMapping(),
            where: ['order_id' => $orderId],
            orderBy: ['position' => 'ASC'],
        );

        return \array_map(self::toOrderLineFromDto(...), $lineDtos);
    }

    private function createOrderMapping(): Mapping
    {
        return Mapping::auto(OrderDTO::class, 'orders')
            ->ignore('lines');
    }

    private function createOrderLineMapping(): Mapping
    {
        return Mapping::auto(OrderLineDTO::class, 'order_lines')
            ->ignore('lineTotalAmount');
    }

    /**
     * @param list<OrderLine> $lines
     */
    private function toOrderDomain(OrderDTO $dto, array $lines): Order
    {
        return Order::reconstitute(
            id: new OrderId($dto->id),
            status: OrderStatus::from($dto->status),
            customerName: $dto->customerName,
            lines: $lines,
            createdAt: $dto->createdAt,
            updatedAt: $dto->updatedAt,
        );
    }

    private static function toOrderDto(Order $order): OrderDTO
    {
        $total = $order->getTotal();
        $dto = new OrderDTO();
        $dto->id = $order->getId()->value;
        $dto->status = $order->getStatus()->value;
        $dto->customerName = $order->getCustomerName();
        $dto->totalAmount = $total->amount;
        $dto->totalCurrency = $total->currency;
        $dto->createdAt = $order->getCreatedAt();
        $dto->updatedAt = $order->getUpdatedAt();

        return $dto;
    }

    private static function toOrderLineDto(string $orderId, int $position, OrderLine $line): OrderLineDTO
    {
        $dto = new OrderLineDTO();
        $dto->orderId = $orderId;
        $dto->position = $position;
        $dto->productId = $line->getProductId()->value;
        $dto->productName = $line->getProductName();
        $dto->unitPriceAmount = $line->getUnitPrice()->amount;
        $dto->unitPriceCurrency = $line->getUnitPrice()->currency;
        $dto->quantity = $line->getQuantity();
        $dto->lineTotalAmount = $line->getLineTotal()->amount;

        return $dto;
    }

    private static function toOrderLineFromDto(OrderLineDTO $dto): OrderLine
    {
        return new OrderLine(
            productId: new ProductId($dto->productId),
            productName: $dto->productName,
            unitPrice: new Money($dto->unitPriceAmount, $dto->unitPriceCurrency),
            quantity: $dto->quantity,
        );
    }

}

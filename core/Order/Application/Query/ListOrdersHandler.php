<?php

declare(strict_types=1);

namespace Core\Order\Application\Query;

use Core\Order\Application\DTO\OrderDTO;
use Core\Order\Application\DTO\OrderLineDTO;
use Core\Order\Domain\Entity\Order;
use Core\Order\Domain\Repository\OrderRepositoryInterface;
use Core\SharedKernel\CQRS\AsQueryHandler;

#[AsQueryHandler]
final readonly class ListOrdersHandler
{
    public function __construct(
        private OrderRepositoryInterface $repository,
    ) {}

    /**
     * @return list<OrderDTO>
     */
    public function __invoke(ListOrders $query): array
    {
        $orders = $this->repository->findAll($query->limit, $query->offset);

        return \array_map(static function (Order $order): OrderDTO {
            $dto = new OrderDTO();
            $dto->id = $order->getId()->value;
            $dto->status = $order->getStatus()->value;
            $dto->customerName = $order->getCustomerName();
            $total = $order->getTotal();
            $dto->totalAmount = $total->amount;
            $dto->totalCurrency = $total->currency;
            $dto->createdAt = $order->getCreatedAt();
            $dto->updatedAt = $order->getUpdatedAt();

            $dto->lines = \array_map(static function ($line): OrderLineDTO {
                $lineDto = new OrderLineDTO();
                $lineDto->productId = $line->getProductId()->value;
                $lineDto->productName = $line->getProductName();
                $lineDto->unitPriceAmount = $line->getUnitPrice()->amount;
                $lineDto->unitPriceCurrency = $line->getUnitPrice()->currency;
                $lineDto->quantity = $line->getQuantity();
                $lineDto->lineTotalAmount = $line->getLineTotal()->amount;

                return $lineDto;
            }, $order->getLines());

            return $dto;
        }, $orders);
    }
}

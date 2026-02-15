<?php

declare(strict_types=1);

namespace Core\Order\Application\Command;

use Core\Order\Domain\Entity\Order;
use Core\Order\Domain\Entity\OrderLine;
use Core\Order\Domain\Repository\OrderRepositoryInterface;
use Core\Order\Domain\ValueObject\OrderId;
use Core\Product\Domain\Exception\ProductNotFoundException;
use Core\Product\Domain\Repository\ProductRepositoryInterface;
use Core\Product\Domain\ValueObject\ProductId;
use Core\SharedKernel\CQRS\AsCommandHandler;

#[AsCommandHandler]
final readonly class PlaceOrderHandler
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private ProductRepositoryInterface $productRepository,
    ) {}

    public function __invoke(PlaceOrder $command): void
    {
        $orderLines = [];

        foreach ($command->lines as $line) {
            $productId = new ProductId($line->productId);
            $product = $this->productRepository->findById($productId);

            if ($product === null) {
                throw ProductNotFoundException::withId($productId);
            }

            $orderLines[] = new OrderLine(
                productId: $productId,
                productName: $product->getName()->value,
                unitPrice: $product->getPrice(),
                quantity: $line->quantity,
            );
        }

        $order = Order::place(
            id: OrderId::generate(),
            customerName: $command->customerName,
            lines: $orderLines,
        );

        $this->orderRepository->save($order);
    }
}

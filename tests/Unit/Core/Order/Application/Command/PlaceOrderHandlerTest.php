<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Order\Application\Command;

use Core\Order\Application\Command\PlaceOrder;
use Core\Order\Application\Command\PlaceOrderHandler;
use Core\Order\Application\Command\PlaceOrderLine;
use Core\Order\Domain\Entity\Order;
use Core\Order\Domain\Repository\OrderRepositoryInterface;
use Core\Order\Domain\ValueObject\OrderStatus;
use Core\Product\Domain\Entity\Product;
use Core\Product\Domain\Exception\ProductNotFoundException;
use Core\Product\Domain\Repository\ProductRepositoryInterface;
use Core\Product\Domain\ValueObject\Money;
use Core\Product\Domain\ValueObject\ProductId;
use Core\Product\Domain\ValueObject\ProductName;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PlaceOrderHandlerTest extends TestCase
{
    #[Test]
    public function it_places_an_order(): void
    {
        $productId = ProductId::generate();

        $product = Product::create(
            $productId,
            new ProductName('Widget'),
            new Money(1000, 'USD'),
        );

        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->method('findById')->willReturn($product);

        $orderRepo = $this->createMock(OrderRepositoryInterface::class);
        $orderRepo->expects(self::once())
            ->method('save')
            ->with(self::callback(static function (Order $order): bool {
                return $order->getStatus() === OrderStatus::Pending
                    && $order->getCustomerName() === 'Alice'
                    && \count($order->getLines()) === 1
                    && $order->getTotal()->amount === 2000;
            }));

        $handler = new PlaceOrderHandler($orderRepo, $productRepo);

        ($handler)(new PlaceOrder(
            customerName: 'Alice',
            lines: [
                new PlaceOrderLine(productId: $productId->value, quantity: 2),
            ],
        ));
    }

    #[Test]
    public function it_throws_when_product_not_found(): void
    {
        $productRepo = $this->createMock(ProductRepositoryInterface::class);
        $productRepo->method('findById')->willReturn(null);

        $orderRepo = $this->createMock(OrderRepositoryInterface::class);

        $handler = new PlaceOrderHandler($orderRepo, $productRepo);

        $this->expectException(ProductNotFoundException::class);

        ($handler)(new PlaceOrder(
            customerName: 'Bob',
            lines: [
                new PlaceOrderLine(productId: ProductId::generate()->value, quantity: 1),
            ],
        ));
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Order\Application\Command;

use Core\Order\Application\Command\CancelOrder;
use Core\Order\Application\Command\CancelOrderHandler;
use Core\Order\Domain\Entity\Order;
use Core\Order\Domain\Entity\OrderLine;
use Core\Order\Domain\Exception\OrderNotFoundException;
use Core\Order\Domain\Repository\OrderRepositoryInterface;
use Core\Order\Domain\ValueObject\OrderId;
use Core\Order\Domain\ValueObject\OrderStatus;
use Core\Product\Domain\ValueObject\Money;
use Core\Product\Domain\ValueObject\ProductId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CancelOrderHandlerTest extends TestCase
{
    #[Test]
    public function it_cancels_a_pending_order(): void
    {
        $orderId = OrderId::generate();
        $order = Order::place($orderId, 'Alice', [
            new OrderLine(ProductId::generate(), 'W', new Money(100, 'USD'), 1),
        ]);

        $repository = $this->createMock(OrderRepositoryInterface::class);
        $repository->method('findById')->willReturn($order);
        $repository->expects(self::once())->method('save');

        $handler = new CancelOrderHandler($repository);

        ($handler)(new CancelOrder($orderId->value));

        self::assertSame(OrderStatus::Cancelled, $order->getStatus());
    }

    #[Test]
    public function it_throws_when_order_not_found(): void
    {
        $repository = $this->createMock(OrderRepositoryInterface::class);
        $repository->method('findById')->willReturn(null);

        $handler = new CancelOrderHandler($repository);

        $this->expectException(OrderNotFoundException::class);

        ($handler)(new CancelOrder(OrderId::generate()->value));
    }
}

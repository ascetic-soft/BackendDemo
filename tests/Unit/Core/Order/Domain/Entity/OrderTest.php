<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Order\Domain\Entity;

use Core\Order\Domain\Entity\Order;
use Core\Order\Domain\Entity\OrderLine;
use Core\Order\Domain\ValueObject\OrderId;
use Core\Order\Domain\ValueObject\OrderStatus;
use Core\Product\Domain\ValueObject\Money;
use Core\Product\Domain\ValueObject\ProductId;
use DateTimeImmutable;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class OrderTest extends TestCase
{
    #[Test]
    public function it_places_an_order(): void
    {
        $order = $this->placeOrder();

        self::assertSame(OrderStatus::Pending, $order->getStatus());
        self::assertSame('John Doe', $order->getCustomerName());
        self::assertCount(2, $order->getLines());
        self::assertNotNull($order->getCreatedAt());
    }

    #[Test]
    public function it_rejects_order_without_lines(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('at least one line item');

        Order::place(OrderId::generate(), 'John', []);
    }

    #[Test]
    public function it_calculates_total(): void
    {
        $order = $this->placeOrder();
        $total = $order->getTotal();

        // Line 1: 1000 * 2 = 2000
        // Line 2: 500 * 3 = 1500
        // Total: 3500
        self::assertSame(3500, $total->amount);
        self::assertSame('USD', $total->currency);
    }

    #[Test]
    public function it_confirms_pending_order(): void
    {
        $order = $this->placeOrder();

        $order->confirm();

        self::assertSame(OrderStatus::Confirmed, $order->getStatus());
    }

    #[Test]
    public function it_cancels_pending_order(): void
    {
        $order = $this->placeOrder();

        $order->cancel();

        self::assertSame(OrderStatus::Cancelled, $order->getStatus());
    }

    #[Test]
    public function it_completes_confirmed_order(): void
    {
        $order = $this->placeOrder();
        $order->confirm();

        $order->complete();

        self::assertSame(OrderStatus::Completed, $order->getStatus());
    }

    #[Test]
    public function it_cannot_confirm_cancelled_order(): void
    {
        $order = $this->placeOrder();
        $order->cancel();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot transition');

        $order->confirm();
    }

    #[Test]
    public function it_cannot_complete_pending_order(): void
    {
        $order = $this->placeOrder();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot transition');

        $order->complete();
    }

    #[Test]
    public function it_reconstitutes_from_persistence(): void
    {
        $id = OrderId::generate();
        $lines = $this->createLines();
        $createdAt = new DateTimeImmutable('2025-01-01');
        $updatedAt = new DateTimeImmutable('2025-06-01');

        $order = Order::reconstitute($id, OrderStatus::Confirmed, 'Jane', $lines, $createdAt, $updatedAt);

        self::assertTrue($order->getId()->equals($id));
        self::assertSame(OrderStatus::Confirmed, $order->getStatus());
        self::assertSame('Jane', $order->getCustomerName());
        self::assertCount(2, $order->getLines());
    }

    private function placeOrder(): Order
    {
        return Order::place(
            OrderId::generate(),
            'John Doe',
            $this->createLines(),
        );
    }

    /**
     * @return list<OrderLine>
     */
    private function createLines(): array
    {
        return [
            new OrderLine(
                ProductId::generate(),
                'Widget A',
                new Money(1000, 'USD'),
                2,
            ),
            new OrderLine(
                ProductId::generate(),
                'Widget B',
                new Money(500, 'USD'),
                3,
            ),
        ];
    }
}

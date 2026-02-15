<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Order\Domain\Entity;

use Core\Order\Domain\Entity\OrderLine;
use Core\Product\Domain\ValueObject\Money;
use Core\Product\Domain\ValueObject\ProductId;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class OrderLineTest extends TestCase
{
    #[Test]
    public function it_creates_order_line(): void
    {
        $productId = ProductId::generate();
        $unitPrice = new Money(1500, 'USD');

        $line = new OrderLine($productId, 'Gadget', $unitPrice, 3);

        self::assertTrue($line->getProductId()->equals($productId));
        self::assertSame('Gadget', $line->getProductName());
        self::assertSame(1500, $line->getUnitPrice()->amount);
        self::assertSame(3, $line->getQuantity());
    }

    #[Test]
    public function it_calculates_line_total(): void
    {
        $line = new OrderLine(
            ProductId::generate(),
            'Gadget',
            new Money(1500, 'USD'),
            3,
        );

        $total = $line->getLineTotal();

        self::assertSame(4500, $total->amount);
        self::assertSame('USD', $total->currency);
    }

    #[Test]
    public function it_rejects_zero_quantity(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('at least 1');

        new OrderLine(
            ProductId::generate(),
            'Gadget',
            new Money(1500, 'USD'),
            0,
        );
    }

    #[Test]
    public function it_rejects_negative_quantity(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new OrderLine(
            ProductId::generate(),
            'Gadget',
            new Money(1500, 'USD'),
            -1,
        );
    }
}

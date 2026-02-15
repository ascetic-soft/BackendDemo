<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Product\Domain\ValueObject;

use Core\Product\Domain\ValueObject\Money;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    #[Test]
    public function it_creates_with_valid_amount(): void
    {
        $money = new Money(1999, 'USD');

        self::assertSame(1999, $money->amount);
        self::assertSame('USD', $money->currency);
    }

    #[Test]
    public function it_rejects_negative_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot be negative');

        new Money(-1, 'USD');
    }

    #[Test]
    public function it_rejects_invalid_currency_code(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('3-letter ISO 4217');

        new Money(100, 'US');
    }

    #[Test]
    public function it_adds_same_currency(): void
    {
        $a = new Money(1000, 'USD');
        $b = new Money(500, 'USD');
        $result = $a->add($b);

        self::assertSame(1500, $result->amount);
        self::assertSame('USD', $result->currency);
    }

    #[Test]
    public function it_rejects_adding_different_currencies(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('different currencies');

        $a = new Money(1000, 'USD');
        $b = new Money(500, 'EUR');
        $a->add($b);
    }

    #[Test]
    public function it_multiplies(): void
    {
        $money = new Money(500, 'USD');
        $result = $money->multiply(3);

        self::assertSame(1500, $result->amount);
        self::assertSame('USD', $result->currency);
    }

    #[Test]
    public function it_rejects_negative_multiplier(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $money = new Money(500, 'USD');
        $money->multiply(-1);
    }

    #[Test]
    public function equals_returns_true_for_same_values(): void
    {
        $a = new Money(1000, 'USD');
        $b = new Money(1000, 'USD');

        self::assertTrue($a->equals($b));
    }

    #[Test]
    public function equals_returns_false_for_different_amount(): void
    {
        $a = new Money(1000, 'USD');
        $b = new Money(999, 'USD');

        self::assertFalse($a->equals($b));
    }

    #[Test]
    public function equals_returns_false_for_different_currency(): void
    {
        $a = new Money(1000, 'USD');
        $b = new Money(1000, 'EUR');

        self::assertFalse($a->equals($b));
    }
}

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
    public function itCreatesWithValidAmount(): void
    {
        $money = new Money(1999, 'USD');

        self::assertSame(1999, $money->amount);
        self::assertSame('USD', $money->currency);
    }

    #[Test]
    public function itRejectsNegativeAmount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot be negative');

        new Money(-1, 'USD');
    }

    #[Test]
    public function itRejectsInvalidCurrencyCode(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('3-letter ISO 4217');

        new Money(100, 'US');
    }

    #[Test]
    public function itAddsSameCurrency(): void
    {
        $a = new Money(1000, 'USD');
        $b = new Money(500, 'USD');
        $result = $a->add($b);

        self::assertSame(1500, $result->amount);
        self::assertSame('USD', $result->currency);
    }

    #[Test]
    public function itRejectsAddingDifferentCurrencies(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('different currencies');

        $a = new Money(1000, 'USD');
        $b = new Money(500, 'EUR');
        $a->add($b);
    }

    #[Test]
    public function itMultiplies(): void
    {
        $money = new Money(500, 'USD');
        $result = $money->multiply(3);

        self::assertSame(1500, $result->amount);
        self::assertSame('USD', $result->currency);
    }

    #[Test]
    public function itRejectsNegativeMultiplier(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $money = new Money(500, 'USD');
        $money->multiply(-1);
    }

    #[Test]
    public function equalsReturnsTrueForSameValues(): void
    {
        $a = new Money(1000, 'USD');
        $b = new Money(1000, 'USD');

        self::assertTrue($a->equals($b));
    }

    #[Test]
    public function equalsReturnsFalseForDifferentAmount(): void
    {
        $a = new Money(1000, 'USD');
        $b = new Money(999, 'USD');

        self::assertFalse($a->equals($b));
    }

    #[Test]
    public function equalsReturnsFalseForDifferentCurrency(): void
    {
        $a = new Money(1000, 'USD');
        $b = new Money(1000, 'EUR');

        self::assertFalse($a->equals($b));
    }
}

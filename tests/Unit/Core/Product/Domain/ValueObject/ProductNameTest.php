<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Product\Domain\ValueObject;

use Core\Product\Domain\ValueObject\ProductName;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ProductNameTest extends TestCase
{
    #[Test]
    public function it_creates_with_valid_name(): void
    {
        $name = new ProductName('Laptop Pro');

        self::assertSame('Laptop Pro', $name->value);
    }

    #[Test]
    public function it_trims_whitespace(): void
    {
        $name = new ProductName('  Laptop Pro  ');

        self::assertSame('Laptop Pro', $name->value);
    }

    #[Test]
    public function it_rejects_empty_name(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot be empty');

        new ProductName('');
    }

    #[Test]
    public function it_rejects_whitespace_only(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot be empty');

        new ProductName('   ');
    }

    #[Test]
    public function it_rejects_name_exceeding_255_chars(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot exceed 255 characters');

        new ProductName(\str_repeat('a', 256));
    }

    #[Test]
    public function it_accepts_255_char_name(): void
    {
        $name = new ProductName(\str_repeat('a', 255));

        self::assertSame(255, \mb_strlen($name->value));
    }

    #[Test]
    public function equals_returns_true_for_same_value(): void
    {
        $name1 = new ProductName('Laptop');
        $name2 = new ProductName('Laptop');

        self::assertTrue($name1->equals($name2));
    }

    #[Test]
    public function equals_returns_false_for_different_value(): void
    {
        $name1 = new ProductName('Laptop');
        $name2 = new ProductName('Desktop');

        self::assertFalse($name1->equals($name2));
    }
}

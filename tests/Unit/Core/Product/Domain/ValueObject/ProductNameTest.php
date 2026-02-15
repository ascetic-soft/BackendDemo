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
    public function itCreatesWithValidName(): void
    {
        $name = new ProductName('Laptop Pro');

        self::assertSame('Laptop Pro', $name->value);
    }

    #[Test]
    public function itTrimsWhitespace(): void
    {
        $name = new ProductName('  Laptop Pro  ');

        self::assertSame('Laptop Pro', $name->value);
    }

    #[Test]
    public function itRejectsEmptyName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot be empty');

        new ProductName('');
    }

    #[Test]
    public function itRejectsWhitespaceOnly(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot be empty');

        new ProductName('   ');
    }

    #[Test]
    public function itRejectsNameExceeding255Chars(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot exceed 255 characters');

        new ProductName(\str_repeat('a', 256));
    }

    #[Test]
    public function itAccepts255CharName(): void
    {
        $name = new ProductName(\str_repeat('a', 255));

        self::assertSame(255, \mb_strlen($name->value));
    }

    #[Test]
    public function equalsReturnsTrueForSameValue(): void
    {
        $name1 = new ProductName('Laptop');
        $name2 = new ProductName('Laptop');

        self::assertTrue($name1->equals($name2));
    }

    #[Test]
    public function equalsReturnsFalseForDifferentValue(): void
    {
        $name1 = new ProductName('Laptop');
        $name2 = new ProductName('Desktop');

        self::assertFalse($name1->equals($name2));
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Product\Domain\Entity;

use Core\Product\Domain\Entity\Product;
use Core\Product\Domain\ValueObject\Money;
use Core\Product\Domain\ValueObject\ProductId;
use Core\Product\Domain\ValueObject\ProductName;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ProductTest extends TestCase
{
    #[Test]
    public function it_creates_a_product(): void
    {
        $id = ProductId::generate();
        $name = new ProductName('Laptop Pro');
        $price = new Money(99900, 'USD');

        $product = Product::create($id, $name, $price, 'A great laptop');

        self::assertTrue($product->getId()->equals($id));
        self::assertSame('Laptop Pro', $product->getName()->value);
        self::assertSame(99900, $product->getPrice()->amount);
        self::assertSame('A great laptop', $product->getDescription());
        self::assertNotNull($product->getCreatedAt());
        self::assertNotNull($product->getUpdatedAt());
    }

    #[Test]
    public function it_renames_a_product(): void
    {
        $product = $this->createProduct();
        $oldUpdatedAt = $product->getUpdatedAt();

        $product->rename(new ProductName('New Name'));

        self::assertSame('New Name', $product->getName()->value);
        self::assertGreaterThanOrEqual($oldUpdatedAt, $product->getUpdatedAt());
    }

    #[Test]
    public function it_changes_price(): void
    {
        $product = $this->createProduct();

        $product->changePrice(new Money(5000, 'EUR'));

        self::assertSame(5000, $product->getPrice()->amount);
        self::assertSame('EUR', $product->getPrice()->currency);
    }

    #[Test]
    public function it_updates_description(): void
    {
        $product = $this->createProduct();

        $product->updateDescription('Updated description');

        self::assertSame('Updated description', $product->getDescription());
    }

    #[Test]
    public function it_reconstitutes_from_persistence(): void
    {
        $id = ProductId::generate();
        $name = new ProductName('Keyboard');
        $price = new Money(4999, 'USD');
        $createdAt = new DateTimeImmutable('2025-01-01 12:00:00');
        $updatedAt = new DateTimeImmutable('2025-06-01 12:00:00');

        $product = Product::reconstitute($id, $name, $price, 'Mechanical', $createdAt, $updatedAt);

        self::assertTrue($product->getId()->equals($id));
        self::assertSame('Keyboard', $product->getName()->value);
        self::assertSame(4999, $product->getPrice()->amount);
        self::assertSame('Mechanical', $product->getDescription());
        self::assertEquals($createdAt, $product->getCreatedAt());
        self::assertEquals($updatedAt, $product->getUpdatedAt());
    }

    private function createProduct(): Product
    {
        return Product::create(
            ProductId::generate(),
            new ProductName('Test Product'),
            new Money(1000, 'USD'),
            'A test product',
        );
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Product\Application\Command;

use Core\Product\Application\Command\UpdateProduct;
use Core\Product\Application\Command\UpdateProductHandler;
use Core\Product\Domain\Entity\Product;
use Core\Product\Domain\Exception\ProductNotFoundException;
use Core\Product\Domain\Repository\ProductRepositoryInterface;
use Core\Product\Domain\ValueObject\Money;
use Core\Product\Domain\ValueObject\ProductId;
use Core\Product\Domain\ValueObject\ProductName;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class UpdateProductHandlerTest extends TestCase
{
    private ProductId $productId;

    protected function setUp(): void
    {
        $this->productId = new ProductId('550e8400-e29b-41d4-a716-446655440000');
    }

    #[Test]
    public function itUpdatesProductName(): void
    {
        $product = Product::create($this->productId, new ProductName('Old'), new Money(100, 'USD'));
        $repository = $this->createMock(ProductRepositoryInterface::class);

        $repository->method('findById')->willReturn($product);
        $repository->expects($this->once())->method('save');

        $handler = new UpdateProductHandler($repository);

        ($handler)(new UpdateProduct(id: $this->productId->value, name: 'New Name'));

        self::assertSame('New Name', $product->getName()->value);
    }

    #[Test]
    public function itUpdatesProductPrice(): void
    {
        $product = Product::create($this->productId, new ProductName('Widget'), new Money(100, 'USD'));
        $repository = $this->createMock(ProductRepositoryInterface::class);

        $repository->method('findById')->willReturn($product);
        $repository->expects($this->once())->method('save');

        $handler = new UpdateProductHandler($repository);

        ($handler)(new UpdateProduct(id: $this->productId->value, priceAmount: 5000, priceCurrency: 'EUR'));

        self::assertSame(5000, $product->getPrice()->amount);
        self::assertSame('EUR', $product->getPrice()->currency);
    }

    #[Test]
    public function itThrowsWhenProductNotFound(): void
    {
        $repository = $this->createMock(ProductRepositoryInterface::class);
        $repository->method('findById')->willReturn(null);

        $handler = new UpdateProductHandler($repository);

        $this->expectException(ProductNotFoundException::class);

        ($handler)(new UpdateProduct(id: $this->productId->value, name: 'X'));
    }
}

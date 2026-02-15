<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Product\Application\Command;

use Core\Product\Application\Command\CreateProduct;
use Core\Product\Application\Command\CreateProductHandler;
use Core\Product\Domain\Entity\Product;
use Core\Product\Domain\Repository\ProductRepositoryInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CreateProductHandlerTest extends TestCase
{
    #[Test]
    public function it_creates_and_saves_a_product(): void
    {
        $repository = $this->createMock(ProductRepositoryInterface::class);

        $repository->expects(self::once())
            ->method('save')
            ->with(self::callback(static function (Product $product): bool {
                return $product->getName()->value === 'Widget'
                    && $product->getPrice()->amount === 1999
                    && $product->getPrice()->currency === 'USD'
                    && $product->getDescription() === 'A nice widget';
            }));

        $handler = new CreateProductHandler($repository);

        ($handler)(new CreateProduct(
            name: 'Widget',
            priceAmount: 1999,
            priceCurrency: 'USD',
            description: 'A nice widget',
        ));
    }
}

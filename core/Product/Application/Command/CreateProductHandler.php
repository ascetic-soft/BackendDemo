<?php

declare(strict_types=1);

namespace Core\Product\Application\Command;

use Core\Product\Domain\Entity\Product;
use Core\Product\Domain\Repository\ProductRepositoryInterface;
use Core\Product\Domain\ValueObject\Money;
use Core\Product\Domain\ValueObject\ProductId;
use Core\Product\Domain\ValueObject\ProductName;
use Core\SharedKernel\CQRS\AsCommandHandler;

#[AsCommandHandler(CreateProduct::class)]
final readonly class CreateProductHandler
{
    public function __construct(
        private ProductRepositoryInterface $repository,
    ) {}

    public function __invoke(CreateProduct $command): void
    {
        $product = Product::create(
            id: ProductId::generate(),
            name: new ProductName($command->name),
            price: new Money($command->priceAmount, $command->priceCurrency),
            description: $command->description,
        );

        $this->repository->save($product);
    }
}

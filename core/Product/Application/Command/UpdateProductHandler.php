<?php

declare(strict_types=1);

namespace Core\Product\Application\Command;

use Core\Product\Domain\Exception\ProductNotFoundException;
use Core\Product\Domain\Repository\ProductRepositoryInterface;
use Core\Product\Domain\ValueObject\Money;
use Core\Product\Domain\ValueObject\ProductId;
use Core\Product\Domain\ValueObject\ProductName;
use Core\SharedKernel\CQRS\AsCommandHandler;

#[AsCommandHandler]
final readonly class UpdateProductHandler
{
    public function __construct(
        private ProductRepositoryInterface $repository,
    ) {}

    public function __invoke(UpdateProduct $command): void
    {
        $productId = new ProductId($command->id);
        $product = $this->repository->findById($productId);

        if ($product === null) {
            throw ProductNotFoundException::withId($productId);
        }

        if ($command->name !== null) {
            $product->rename(new ProductName($command->name));
        }

        if ($command->priceAmount !== null) {
            $product->changePrice(
                new Money($command->priceAmount, $command->priceCurrency ?? $product->getPrice()->currency),
            );
        }

        if ($command->description !== null) {
            $product->updateDescription($command->description);
        }

        $this->repository->save($product);
    }
}

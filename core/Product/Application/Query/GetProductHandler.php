<?php

declare(strict_types=1);

namespace Core\Product\Application\Query;

use Core\Product\Application\DTO\ProductDTO;
use Core\Product\Domain\Exception\ProductNotFoundException;
use Core\Product\Domain\Repository\ProductRepositoryInterface;
use Core\Product\Domain\ValueObject\ProductId;
use Core\SharedKernel\CQRS\AsQueryHandler;

#[AsQueryHandler]
final readonly class GetProductHandler
{
    public function __construct(
        private ProductRepositoryInterface $repository,
    ) {}

    public function __invoke(GetProduct $query): ProductDTO
    {
        $productId = new ProductId($query->id);
        $product = $this->repository->findById($productId);

        if ($product === null) {
            throw ProductNotFoundException::withId($productId);
        }

        $dto = new ProductDTO();
        $dto->id = $product->getId()->value;
        $dto->name = $product->getName()->value;
        $dto->priceAmount = $product->getPrice()->amount;
        $dto->priceCurrency = $product->getPrice()->currency;
        $dto->description = $product->getDescription();
        $dto->createdAt = $product->getCreatedAt();
        $dto->updatedAt = $product->getUpdatedAt();

        return $dto;
    }
}

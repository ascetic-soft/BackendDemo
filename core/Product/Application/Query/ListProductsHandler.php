<?php

declare(strict_types=1);

namespace Core\Product\Application\Query;

use Core\Product\Application\DTO\ProductDTO;
use Core\Product\Domain\Repository\ProductRepositoryInterface;
use Core\SharedKernel\CQRS\QueryHandlerInterface;

final readonly class ListProductsHandler implements QueryHandlerInterface
{
    public function __construct(
        private ProductRepositoryInterface $repository,
    ) {}

    /**
     * @return list<ProductDTO>
     */
    public function __invoke(object $query): array
    {
        \assert($query instanceof ListProducts);

        $products = $this->repository->findAll($query->limit, $query->offset);

        return \array_map(static function ($product): ProductDTO {
            $dto = new ProductDTO();
            $dto->id = $product->getId()->value;
            $dto->name = $product->getName()->value;
            $dto->priceAmount = $product->getPrice()->amount;
            $dto->priceCurrency = $product->getPrice()->currency;
            $dto->description = $product->getDescription();
            $dto->createdAt = $product->getCreatedAt();
            $dto->updatedAt = $product->getUpdatedAt();

            return $dto;
        }, $products);
    }
}

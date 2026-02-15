<?php

declare(strict_types=1);

namespace Core\Product\Domain\Repository;

use Core\Product\Domain\Entity\Product;
use Core\Product\Domain\ValueObject\ProductId;

/**
 * Repository interface for Product aggregate persistence.
 */
interface ProductRepositoryInterface
{
    public function findById(ProductId $id): ?Product;

    /**
     * @return list<Product>
     */
    public function findAll(int $limit = 50, int $offset = 0): array;

    public function save(Product $product): void;

    public function delete(ProductId $id): void;
}

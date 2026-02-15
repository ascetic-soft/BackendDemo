<?php

declare(strict_types=1);

namespace Core\Product\Application\DTO;

use DateTimeImmutable;

/**
 * Data Transfer Object for Product read operations.
 * Used by Rowcast DataMapper for hydration.
 */
final class ProductDTO
{
    public string $id;
    public string $name;
    public int $priceAmount;
    public string $priceCurrency;
    public string $description;
    public DateTimeImmutable $createdAt;
    public DateTimeImmutable $updatedAt;
}

<?php

declare(strict_types=1);

namespace Core\Product\Domain\Entity;

use Core\Product\Domain\ValueObject\Money;
use Core\Product\Domain\ValueObject\ProductId;
use Core\Product\Domain\ValueObject\ProductName;
use DateTimeImmutable;

/**
 * Product aggregate root.
 */
final class Product
{
    private function __construct(
        private readonly ProductId $id,
        private ProductName $name,
        private Money $price,
        private string $description,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {}

    public static function create(
        ProductId $id,
        ProductName $name,
        Money $price,
        string $description = '',
    ): self {
        $now = new DateTimeImmutable();

        return new self($id, $name, $price, $description, $now, $now);
    }

    /**
     * Reconstruct from persistence.
     */
    public static function reconstitute(
        ProductId $id,
        ProductName $name,
        Money $price,
        string $description,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self($id, $name, $price, $description, $createdAt, $updatedAt);
    }

    public function rename(ProductName $name): void
    {
        $this->name = $name;
        $this->touch();
    }

    public function changePrice(Money $price): void
    {
        $this->price = $price;
        $this->touch();
    }

    public function updateDescription(string $description): void
    {
        $this->description = $description;
        $this->touch();
    }

    public function getId(): ProductId
    {
        return $this->id;
    }

    public function getName(): ProductName
    {
        return $this->name;
    }

    public function getPrice(): Money
    {
        return $this->price;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    private function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}

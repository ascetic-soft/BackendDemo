<?php

declare(strict_types=1);

namespace App\Repository;

use AsceticSoft\Rowcast\Connection;
use AsceticSoft\Rowcast\DataMapper;
use AsceticSoft\Rowcast\Mapping;
use Core\Product\Application\DTO\ProductDTO;
use Core\Product\Domain\Entity\Product;
use Core\Product\Domain\Repository\ProductRepositoryInterface;
use Core\Product\Domain\ValueObject\Money;
use Core\Product\Domain\ValueObject\ProductId;
use Core\Product\Domain\ValueObject\ProductName;

final readonly class ProductRepository implements ProductRepositoryInterface
{
    private DataMapper $mapper;

    public function __construct(
        private Connection $connection,
    ) {
        $this->mapper = new DataMapper($this->connection);
    }

    public function findById(ProductId $id): ?Product
    {
        $mapping = $this->createMapping();
        /** @var ProductDTO|null $dto */
        $dto = $this->mapper->findOne($mapping, ['id' => $id->value]);

        if ($dto === null) {
            return null;
        }

        return self::toDomain($dto);
    }

    /**
     * @return list<Product>
     */
    public function findAll(int $limit = 50, int $offset = 0): array
    {
        $mapping = $this->createMapping();
        /** @var list<ProductDTO> $dtos */
        $dtos = $this->mapper->findAll($mapping, limit: $limit, offset: $offset, orderBy: ['created_at' => 'DESC']);

        return \array_map(self::toDomain(...), $dtos);
    }

    public function save(Product $product): void
    {
        $data = new ProductDTO();
        $data->id = $product->getId()->value;
        $data->name = $product->getName()->value;
        $data->priceAmount = $product->getPrice()->amount;
        $data->priceCurrency = $product->getPrice()->currency;
        $data->description = $product->getDescription();
        $data->createdAt = $product->getCreatedAt();
        $data->updatedAt = $product->getUpdatedAt();

        $this->mapper->save($this->createMapping(), $data, 'id');
    }

    public function delete(ProductId $id): void
    {
        $this->mapper->delete($this->createMapping(), ['id' => $id->value]);
    }

    private function createMapping(): Mapping
    {
        return Mapping::auto(ProductDTO::class, 'products');
    }

    private static function toDomain(ProductDTO $dto): Product
    {
        return Product::reconstitute(
            id: new ProductId($dto->id),
            name: new ProductName($dto->name),
            price: new Money($dto->priceAmount, $dto->priceCurrency),
            description: $dto->description,
            createdAt: $dto->createdAt,
            updatedAt: $dto->updatedAt,
        );
    }
}

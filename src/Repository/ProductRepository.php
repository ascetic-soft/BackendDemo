<?php

declare(strict_types=1);

namespace App\Repository;

use AsceticSoft\Rowcast\Connection;
use AsceticSoft\Rowcast\DataMapper;
use AsceticSoft\Rowcast\Mapping\ResultSetMapping;
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
        $rsm = $this->createResultSetMapping();
        /** @var ProductDTO|null $dto */
        $dto = $this->mapper->findOne($rsm, ['id' => $id->value]); // @phpstan-ignore argument.templateType

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
        $rsm = $this->createResultSetMapping();
        /** @var list<ProductDTO> $dtos */
        $dtos = $this->mapper->findAll($rsm, limit: $limit, offset: $offset, orderBy: ['created_at' => 'DESC']); // @phpstan-ignore argument.templateType

        return \array_map(self::toDomain(...), $dtos);
    }

    public function save(Product $product): void
    {
        /** @var ProductDTO|null $existing */
        $existing = $this->mapper->findOne( // @phpstan-ignore argument.templateType
            $this->createResultSetMapping(),
            ['id' => $product->getId()->value],
        );

        $data = new ProductDTO();
        $data->id = $product->getId()->value;
        $data->name = $product->getName()->value;
        $data->priceAmount = $product->getPrice()->amount;
        $data->priceCurrency = $product->getPrice()->currency;
        $data->description = $product->getDescription();
        $data->createdAt = $product->getCreatedAt();
        $data->updatedAt = $product->getUpdatedAt();

        $rsm = $this->createResultSetMapping();

        if ($existing === null) {
            $this->mapper->insert($rsm, $data);
        } else {
            $this->mapper->update($rsm, $data, ['id' => $product->getId()->value]);
        }
    }

    public function delete(ProductId $id): void
    {
        $this->mapper->delete($this->createResultSetMapping(), ['id' => $id->value]);
    }

    private function createResultSetMapping(): ResultSetMapping
    {
        $rsm = new ResultSetMapping(ProductDTO::class, table: 'products');
        $rsm->addField('id', 'id')
            ->addField('name', 'name')
            ->addField('price_amount', 'priceAmount')
            ->addField('price_currency', 'priceCurrency')
            ->addField('description', 'description')
            ->addField('created_at', 'createdAt')
            ->addField('updated_at', 'updatedAt');

        return $rsm;
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

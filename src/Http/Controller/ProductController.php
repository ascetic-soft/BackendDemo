<?php

declare(strict_types=1);

namespace App\Http\Controller;

use AsceticSoft\Waypoint\Attribute\Route;
use Core\Product\Application\Command\CreateProduct;
use Core\Product\Application\Command\UpdateProduct;
use Core\Product\Application\DTO\ProductDTO;
use Core\Product\Application\Query\GetProduct;
use Core\Product\Application\Query\ListProducts;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[Route('/api/products')]
final readonly class ProductController extends AbstractController
{
    #[Route(methods: ['GET'], name: 'products.list')]
    public function list(ServerRequestInterface $request): ResponseInterface
    {
        [$limit, $offset] = self::pagination($request);

        /** @var list<ProductDTO> $products */
        $products = $this->queryBus->dispatch(new ListProducts($limit, $offset));

        return self::json(200, \array_map(self::serializeProduct(...), $products));
    }

    #[Route('/{id}', methods: ['GET'], name: 'products.show')]
    public function show(string $id): ResponseInterface
    {
        /** @var ProductDTO $product */
        $product = $this->queryBus->dispatch(new GetProduct($id));

        return self::json(200, self::serializeProduct($product));
    }

    #[Route(methods: ['POST'], name: 'products.create')]
    public function create(ServerRequestInterface $request): ResponseInterface
    {
        $body = self::parseBody($request);

        $this->commandBus->dispatch(new CreateProduct(
            name: self::str($body, 'name'),
            priceAmount: self::int($body, 'price_amount'),
            priceCurrency: self::str($body, 'price_currency', 'USD'),
            description: self::str($body, 'description'),
        ));

        return self::json(201, ['message' => 'Product created.']);
    }

    #[Route('/{id}', methods: ['PUT'], name: 'products.update')]
    public function update(string $id, ServerRequestInterface $request): ResponseInterface
    {
        $body = self::parseBody($request);

        $this->commandBus->dispatch(new UpdateProduct(
            id: $id,
            name: \array_key_exists('name', $body) ? self::str($body, 'name') : null,
            priceAmount: \array_key_exists('price_amount', $body) ? self::int($body, 'price_amount') : null,
            priceCurrency: \array_key_exists('price_currency', $body) ? self::str($body, 'price_currency') : null,
            description: \array_key_exists('description', $body) ? self::str($body, 'description') : null,
        ));

        return self::json(200, ['message' => 'Product updated.']);
    }

    /**
     * @return array<string, mixed>
     */
    private static function serializeProduct(ProductDTO $dto): array
    {
        return [
            'id' => $dto->id,
            'name' => $dto->name,
            'price' => [
                'amount' => $dto->priceAmount,
                'currency' => $dto->priceCurrency,
            ],
            'description' => $dto->description,
            'created_at' => $dto->createdAt->format('c'),
            'updated_at' => $dto->updatedAt->format('c'),
        ];
    }
}

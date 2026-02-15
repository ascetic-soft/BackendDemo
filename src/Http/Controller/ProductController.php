<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\CQRS\CommandBus;
use App\CQRS\QueryBus;
use AsceticSoft\Waypoint\Attribute\Route;
use Core\Product\Application\Command\CreateProduct;
use Core\Product\Application\Command\UpdateProduct;
use Core\Product\Application\DTO\ProductDTO;
use Core\Product\Application\Query\GetProduct;
use Core\Product\Application\Query\ListProducts;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[Route('/api/products')]
final readonly class ProductController
{
    public function __construct(
        private CommandBus $commandBus,
        private QueryBus $queryBus,
    ) {}

    #[Route('/', methods: ['GET'], name: 'products.list')]
    public function list(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();
        $limit = (int) ($params['limit'] ?? 50);
        $offset = (int) ($params['offset'] ?? 0);

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

    #[Route('/', methods: ['POST'], name: 'products.create')]
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

    /**
     * @param array<string, mixed>|list<array<string, mixed>> $data
     */
    private static function json(int $status, array $data): ResponseInterface
    {
        $body = \json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        $response = new Response($status);
        $response->getBody()->write($body);

        return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    }

    /**
     * @return array<string, string|int|float|bool|null>
     */
    private static function parseBody(ServerRequestInterface $request): array
    {
        /** @var array<string, string|int|float|bool|null> */
        return (array) $request->getParsedBody();
    }

    /**
     * @param array<string, string|int|float|bool|null> $body
     */
    private static function str(array $body, string $key, string $default = ''): string
    {
        return isset($body[$key]) ? (string) $body[$key] : $default;
    }

    /**
     * @param array<string, string|int|float|bool|null> $body
     */
    private static function int(array $body, string $key, int $default = 0): int
    {
        return isset($body[$key]) ? (int) $body[$key] : $default;
    }
}

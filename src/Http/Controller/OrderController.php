<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\CQRS\CommandBus;
use App\CQRS\QueryBus;
use AsceticSoft\Waypoint\Attribute\Route;
use Core\Order\Application\Command\CancelOrder;
use Core\Order\Application\Command\PlaceOrder;
use Core\Order\Application\Command\PlaceOrderLine;
use Core\Order\Application\DTO\OrderDTO;
use Core\Order\Application\DTO\OrderLineDTO;
use Core\Order\Application\Query\GetOrder;
use Core\Order\Application\Query\ListOrders;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[Route('/api/orders')]
final readonly class OrderController
{
    public function __construct(
        private CommandBus $commandBus,
        private QueryBus $queryBus,
    ) {}

    #[Route('/', methods: ['GET'], name: 'orders.list')]
    public function list(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();
        $limit = (int) ($params['limit'] ?? 50);
        $offset = (int) ($params['offset'] ?? 0);

        /** @var list<OrderDTO> $orders */
        $orders = $this->queryBus->dispatch(new ListOrders($limit, $offset));

        return self::json(200, \array_map(self::serializeOrder(...), $orders));
    }

    #[Route('/{id}', methods: ['GET'], name: 'orders.show')]
    public function show(string $id): ResponseInterface
    {
        /** @var OrderDTO $order */
        $order = $this->queryBus->dispatch(new GetOrder($id));

        return self::json(200, self::serializeOrder($order));
    }

    #[Route('/', methods: ['POST'], name: 'orders.create')]
    public function create(ServerRequestInterface $request): ResponseInterface
    {
        /** @var array<string, mixed> $body */
        $body = (array) $request->getParsedBody();

        /** @var list<array<string, string|int|null>> $rawLines */
        $rawLines = \is_array($body['lines'] ?? null) ? $body['lines'] : [];

        $lines = \array_map(
            static fn(array $line): PlaceOrderLine => new PlaceOrderLine(
                productId: isset($line['product_id']) ? (string) $line['product_id'] : '',
                quantity: isset($line['quantity']) ? (int) $line['quantity'] : 1,
            ),
            $rawLines,
        );

        $this->commandBus->dispatch(new PlaceOrder(
            customerName: isset($body['customer_name']) && \is_string($body['customer_name'])
                ? $body['customer_name']
                : '',
            lines: $lines,
        ));

        return self::json(201, ['message' => 'Order placed.']);
    }

    #[Route('/{id}/cancel', methods: ['POST'], name: 'orders.cancel')]
    public function cancel(string $id): ResponseInterface
    {
        $this->commandBus->dispatch(new CancelOrder($id));

        return self::json(200, ['message' => 'Order cancelled.']);
    }

    /**
     * @return array<string, mixed>
     */
    private static function serializeOrder(OrderDTO $dto): array
    {
        return [
            'id' => $dto->id,
            'status' => $dto->status,
            'customer_name' => $dto->customerName,
            'total' => [
                'amount' => $dto->totalAmount,
                'currency' => $dto->totalCurrency,
            ],
            'lines' => \array_map(self::serializeOrderLine(...), $dto->lines),
            'created_at' => $dto->createdAt->format('c'),
            'updated_at' => $dto->updatedAt->format('c'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function serializeOrderLine(OrderLineDTO $dto): array
    {
        return [
            'product_id' => $dto->productId,
            'product_name' => $dto->productName,
            'unit_price' => [
                'amount' => $dto->unitPriceAmount,
                'currency' => $dto->unitPriceCurrency,
            ],
            'quantity' => $dto->quantity,
            'line_total' => $dto->lineTotalAmount,
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
}

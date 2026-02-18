<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\CQRS\CommandBus;
use App\CQRS\QueryBus;
use AsceticSoft\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

abstract readonly class AbstractController
{
    public function __construct(
        protected CommandBus $commandBus,
        protected QueryBus $queryBus,
    ) {}

    /**
     * @param array<string, mixed>|list<array<string, mixed>> $data
     */
    protected static function json(int $status, array $data): ResponseInterface
    {
        $body = \json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        $response = new Response($status);
        $response->getBody()->write($body);

        return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    }

    /**
     * @return array<string, string|int|float|bool|null>
     */
    protected static function parseBody(ServerRequestInterface $request): array
    {
        /** @var array<string, string|int|float|bool|null> */
        return (array) $request->getParsedBody();
    }

    /**
     * @param array<string, string|int|float|bool|null> $body
     */
    protected static function str(array $body, string $key, string $default = ''): string
    {
        return isset($body[$key]) ? (string) $body[$key] : $default;
    }

    /**
     * @param array<string, string|int|float|bool|null> $body
     */
    protected static function int(array $body, string $key, int $default = 0): int
    {
        return isset($body[$key]) ? (int) $body[$key] : $default;
    }

    /**
     * @return array{int, int}
     */
    protected static function pagination(ServerRequestInterface $request, int $defaultLimit = 50): array
    {
        $params = $request->getQueryParams();

        return [
            (int) ($params['limit'] ?? $defaultLimit),
            (int) ($params['offset'] ?? 0),
        ];
    }
}

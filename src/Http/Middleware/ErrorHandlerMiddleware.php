<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use AsceticSoft\Psr7\Response;
use Core\Order\Domain\Exception\OrderNotFoundException;
use Core\Product\Domain\Exception\ProductNotFoundException;
use InvalidArgumentException;
use LogicException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

/**
 * Catches exceptions and converts them to JSON error responses.
 */
final class ErrorHandlerMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (ProductNotFoundException|OrderNotFoundException $e) {
            return self::json(404, $e->getMessage());
        } catch (InvalidArgumentException $e) {
            return self::json(400, $e->getMessage());
        } catch (LogicException $e) {
            return self::json(422, $e->getMessage());
        } catch (Throwable $e) {
            return self::json(500, 'Internal Server Error');
        }
    }

    private static function json(int $status, string $message): ResponseInterface
    {
        $body = \json_encode(['error' => $message], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        $response = new Response($status);
        $response->getBody()->write($body);

        return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}

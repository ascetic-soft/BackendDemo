<?php

declare(strict_types=1);

namespace Core\Order\Application\Command;

use Core\Order\Domain\Exception\OrderNotFoundException;
use Core\Order\Domain\Repository\OrderRepositoryInterface;
use Core\Order\Domain\ValueObject\OrderId;
use Core\SharedKernel\CQRS\AsCommandHandler;

#[AsCommandHandler]
final readonly class CancelOrderHandler
{
    public function __construct(
        private OrderRepositoryInterface $repository,
    ) {}

    public function __invoke(CancelOrder $command): void
    {
        $orderId = new OrderId($command->id);
        $order = $this->repository->findById($orderId);

        if ($order === null) {
            throw OrderNotFoundException::withId($orderId);
        }

        $order->cancel();

        $this->repository->save($order);
    }
}

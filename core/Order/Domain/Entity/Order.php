<?php

declare(strict_types=1);

namespace Core\Order\Domain\Entity;

use Core\Order\Domain\ValueObject\OrderId;
use Core\Order\Domain\ValueObject\OrderStatus;
use Core\Product\Domain\ValueObject\Money;
use DateTimeImmutable;
use InvalidArgumentException;
use LogicException;

/**
 * Order aggregate root.
 */
final class Order
{
    /**
     * @param list<OrderLine> $lines
     */
    private function __construct(
        private readonly OrderId $id,
        private OrderStatus $status,
        private readonly string $customerName,
        private readonly array $lines,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {}

    /**
     * @param list<OrderLine> $lines
     */
    public static function place(
        OrderId $id,
        string $customerName,
        array $lines,
    ): self {
        if ($lines === []) {
            throw new InvalidArgumentException('Order must have at least one line item.');
        }

        $now = new DateTimeImmutable();

        return new self($id, OrderStatus::Pending, $customerName, $lines, $now, $now);
    }

    /**
     * Reconstruct from persistence.
     *
     * @param list<OrderLine> $lines
     */
    public static function reconstitute(
        OrderId $id,
        OrderStatus $status,
        string $customerName,
        array $lines,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self($id, $status, $customerName, $lines, $createdAt, $updatedAt);
    }

    public function confirm(): void
    {
        $this->transitionTo(OrderStatus::Confirmed);
    }

    public function cancel(): void
    {
        $this->transitionTo(OrderStatus::Cancelled);
    }

    public function complete(): void
    {
        $this->transitionTo(OrderStatus::Completed);
    }

    public function getTotal(): Money
    {
        $total = new Money(0, $this->lines[0]->getUnitPrice()->currency);

        foreach ($this->lines as $line) {
            $total = $total->add($line->getLineTotal());
        }

        return $total;
    }

    public function getId(): OrderId
    {
        return $this->id;
    }

    public function getStatus(): OrderStatus
    {
        return $this->status;
    }

    public function getCustomerName(): string
    {
        return $this->customerName;
    }

    /**
     * @return list<OrderLine>
     */
    public function getLines(): array
    {
        return $this->lines;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    private function transitionTo(OrderStatus $target): void
    {
        if (!$this->status->canTransitionTo($target)) {
            throw new LogicException(
                \sprintf('Cannot transition order from "%s" to "%s".', $this->status->value, $target->value),
            );
        }

        $this->status = $target;
        $this->updatedAt = new DateTimeImmutable();
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Order\Domain\ValueObject;

use Core\Order\Domain\ValueObject\OrderStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class OrderStatusTest extends TestCase
{
    /**
     * @return iterable<string, array{OrderStatus, OrderStatus, bool}>
     */
    public static function transitionProvider(): iterable
    {
        yield 'pending -> confirmed' => [OrderStatus::Pending, OrderStatus::Confirmed, true];
        yield 'pending -> cancelled' => [OrderStatus::Pending, OrderStatus::Cancelled, true];
        yield 'pending -> completed' => [OrderStatus::Pending, OrderStatus::Completed, false];

        yield 'confirmed -> completed' => [OrderStatus::Confirmed, OrderStatus::Completed, true];
        yield 'confirmed -> cancelled' => [OrderStatus::Confirmed, OrderStatus::Cancelled, true];
        yield 'confirmed -> pending' => [OrderStatus::Confirmed, OrderStatus::Pending, false];

        yield 'cancelled -> pending' => [OrderStatus::Cancelled, OrderStatus::Pending, false];
        yield 'cancelled -> confirmed' => [OrderStatus::Cancelled, OrderStatus::Confirmed, false];
        yield 'cancelled -> completed' => [OrderStatus::Cancelled, OrderStatus::Completed, false];

        yield 'completed -> pending' => [OrderStatus::Completed, OrderStatus::Pending, false];
        yield 'completed -> cancelled' => [OrderStatus::Completed, OrderStatus::Cancelled, false];
    }

    #[Test]
    #[DataProvider('transitionProvider')]
    public function itValidatesTransitions(OrderStatus $from, OrderStatus $to, bool $expected): void
    {
        self::assertSame($expected, $from->canTransitionTo($to));
    }
}

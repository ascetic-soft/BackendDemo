<?php

declare(strict_types=1);

namespace Core\Order\Domain\ValueObject;

/**
 * Order lifecycle statuses.
 */
enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
    case Completed = 'completed';

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Pending => \in_array($target, [self::Confirmed, self::Cancelled], true),
            self::Confirmed => \in_array($target, [self::Completed, self::Cancelled], true),
            self::Cancelled, self::Completed => false,
        };
    }
}

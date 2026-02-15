<?php

declare(strict_types=1);

namespace Core\Product\Domain\ValueObject;

use InvalidArgumentException;

/**
 * Money value object representing a price in the smallest currency unit (cents).
 */
final readonly class Money
{
    /**
     * @param int    $amount   Amount in the smallest unit (e.g. cents)
     * @param string $currency ISO 4217 currency code
     */
    public function __construct(
        public int $amount,
        public string $currency = 'USD',
    ) {
        if ($amount < 0) {
            throw new InvalidArgumentException('Money amount cannot be negative.');
        }

        if (\strlen($currency) !== 3) {
            throw new InvalidArgumentException('Currency must be a 3-letter ISO 4217 code.');
        }
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amount + $other->amount, $this->currency);
    }

    public function multiply(int $factor): self
    {
        if ($factor < 0) {
            throw new InvalidArgumentException('Multiplication factor cannot be negative.');
        }

        return new self($this->amount * $factor, $this->currency);
    }

    public function equals(self $other): bool
    {
        return $this->amount === $other->amount && $this->currency === $other->currency;
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(
                \sprintf('Cannot operate on different currencies: %s and %s.', $this->currency, $other->currency),
            );
        }
    }
}

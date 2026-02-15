<?php

declare(strict_types=1);

namespace Core\SharedKernel\ValueObject;

use InvalidArgumentException;

/**
 * Base value object for UUID-based identifiers.
 */
abstract readonly class UuidId
{
    public string $value;

    final public function __construct(string $value)
    {
        if (!\preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value)) {
            throw new InvalidArgumentException(\sprintf('Invalid UUID format: %s', $value));
        }

        $this->value = \strtolower($value);
    }

    /**
     * Generate a new random UUID v4.
     */
    public static function generate(): static
    {
        $bytes = \random_bytes(16);

        $bytes[6] = \chr(\ord($bytes[6]) & 0x0F | 0x40);
        $bytes[8] = \chr(\ord($bytes[8]) & 0x3F | 0x80);

        $hex = \bin2hex($bytes);

        $uuid = \sprintf(
            '%s-%s-%s-%s-%s',
            \substr($hex, 0, 8),
            \substr($hex, 8, 4),
            \substr($hex, 12, 4),
            \substr($hex, 16, 4),
            \substr($hex, 20, 12),
        );

        return new static($uuid);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Core\SharedKernel\ValueObject;

use Core\Product\Domain\ValueObject\ProductId;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class UuidIdTest extends TestCase
{
    #[Test]
    public function it_accepts_valid_uuid(): void
    {
        $id = new ProductId('550e8400-e29b-41d4-a716-446655440000');

        self::assertSame('550e8400-e29b-41d4-a716-446655440000', $id->value);
    }

    #[Test]
    public function it_normalizes_to_lowercase(): void
    {
        $id = new ProductId('550E8400-E29B-41D4-A716-446655440000');

        self::assertSame('550e8400-e29b-41d4-a716-446655440000', $id->value);
    }

    #[Test]
    public function it_rejects_invalid_uuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid UUID format');

        new ProductId('not-a-uuid');
    }

    #[Test]
    public function it_generates_valid_uuid_v4(): void
    {
        $id = ProductId::generate();

        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $id->value,
        );
    }

    #[Test]
    public function it_generates_unique_ids(): void
    {
        $id1 = ProductId::generate();
        $id2 = ProductId::generate();

        self::assertFalse($id1->equals($id2));
    }

    #[Test]
    public function equals_returns_true_for_same_value(): void
    {
        $id1 = new ProductId('550e8400-e29b-41d4-a716-446655440000');
        $id2 = new ProductId('550e8400-e29b-41d4-a716-446655440000');

        self::assertTrue($id1->equals($id2));
    }

    #[Test]
    public function to_string_returns_value(): void
    {
        $id = new ProductId('550e8400-e29b-41d4-a716-446655440000');

        self::assertSame('550e8400-e29b-41d4-a716-446655440000', (string) $id);
    }
}

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
    public function itAcceptsValidUuid(): void
    {
        $id = new ProductId('550e8400-e29b-41d4-a716-446655440000');

        self::assertSame('550e8400-e29b-41d4-a716-446655440000', $id->value);
    }

    #[Test]
    public function itNormalizesToLowercase(): void
    {
        $id = new ProductId('550E8400-E29B-41D4-A716-446655440000');

        self::assertSame('550e8400-e29b-41d4-a716-446655440000', $id->value);
    }

    #[Test]
    public function itRejectsInvalidUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid UUID format');

        new ProductId('not-a-uuid');
    }

    #[Test]
    public function itGeneratesValidUuidV4(): void
    {
        $id = ProductId::generate();

        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $id->value,
        );
    }

    #[Test]
    public function itGeneratesUniqueIds(): void
    {
        $id1 = ProductId::generate();
        $id2 = ProductId::generate();

        self::assertFalse($id1->equals($id2));
    }

    #[Test]
    public function equalsReturnsTrueForSameValue(): void
    {
        $id1 = new ProductId('550e8400-e29b-41d4-a716-446655440000');
        $id2 = new ProductId('550e8400-e29b-41d4-a716-446655440000');

        self::assertTrue($id1->equals($id2));
    }

    #[Test]
    public function toStringReturnsValue(): void
    {
        $id = new ProductId('550e8400-e29b-41d4-a716-446655440000');

        self::assertSame('550e8400-e29b-41d4-a716-446655440000', (string) $id);
    }
}

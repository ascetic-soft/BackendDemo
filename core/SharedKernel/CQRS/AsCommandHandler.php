<?php

declare(strict_types=1);

namespace Core\SharedKernel\CQRS;

use Attribute;

/**
 * Marks a class as a command handler.
 *
 * The command class is resolved automatically from the first parameter
 * of the __invoke() method. You can also specify it explicitly.
 *
 * Example:
 *   #[AsCommandHandler]
 *   final readonly class PlaceOrderHandler {
 *       public function __invoke(PlaceOrder $command): void { ... }
 *   }
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class AsCommandHandler
{
    /**
     * @param class-string|null $command The command class this handler processes (resolved from __invoke if omitted)
     */
    public function __construct(
        public ?string $command = null,
    ) {}
}

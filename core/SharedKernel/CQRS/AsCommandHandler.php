<?php

declare(strict_types=1);

namespace Core\SharedKernel\CQRS;

use AsceticSoft\Wirebox\Attribute\AutoconfigureTag;
use Attribute;

/**
 * Marks a class as a command handler.
 *
 * The argument is the command class that this handler processes.
 *
 * Example:
 *   #[AsCommandHandler(PlaceOrder::class)]
 *   final readonly class PlaceOrderHandler { ... }
 */
#[Attribute(Attribute::TARGET_CLASS)]
#[AutoconfigureTag('command.handler')]
final readonly class AsCommandHandler
{
    /**
     * @param class-string $command The command class this handler processes
     */
    public function __construct(
        public string $command,
    ) {}
}

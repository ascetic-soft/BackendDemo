<?php

declare(strict_types=1);

namespace Core\SharedKernel\CQRS;

use AsceticSoft\Wirebox\Attribute\AutoconfigureTag;
use Attribute;

/**
 * Marks a class as a query handler.
 *
 * The argument is the query class that this handler processes.
 *
 * Example:
 *   #[AsQueryHandler(ListProducts::class)]
 *   final readonly class ListProductsHandler { ... }
 */
#[Attribute(Attribute::TARGET_CLASS)]
#[AutoconfigureTag('query.handler')]
final readonly class AsQueryHandler
{
    /**
     * @param class-string $query The query class this handler processes
     */
    public function __construct(
        public string $query,
    ) {}
}

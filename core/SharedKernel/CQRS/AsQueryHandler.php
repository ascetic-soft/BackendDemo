<?php

declare(strict_types=1);

namespace Core\SharedKernel\CQRS;

use AsceticSoft\Wirebox\Attribute\AutoconfigureTag;
use Attribute;

/**
 * Marks a class as a query handler.
 *
 * The query class is resolved automatically from the first parameter
 * of the __invoke() method. You can also specify it explicitly.
 *
 * Example:
 *   #[AsQueryHandler]
 *   final readonly class ListProductsHandler {
 *       public function __invoke(ListProducts $query): array { ... }
 *   }
 */
#[Attribute(Attribute::TARGET_CLASS)]
#[AutoconfigureTag('query.handler')]
final readonly class AsQueryHandler
{
    /**
     * @param class-string|null $query The query class this handler processes (resolved from __invoke if omitted)
     */
    public function __construct(
        public ?string $query = null,
    ) {}
}

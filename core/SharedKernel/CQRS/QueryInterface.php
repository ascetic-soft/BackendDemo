<?php

declare(strict_types=1);

namespace Core\SharedKernel\CQRS;

/**
 * Marker interface for all queries.
 * Queries represent a request to read data without side effects.
 */
interface QueryInterface {}

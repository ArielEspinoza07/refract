<?php

declare(strict_types=1);

namespace Refract\Examples\Container;

/**
 * Cannot be built: $limit has no class type, no default value, and is not nullable.
 */
final readonly class Report
{
    public function __construct(
        public Logger $logger,
        public int $limit,
    ) {}
}

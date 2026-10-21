<?php

declare(strict_types=1);

namespace Refract\Examples\Routing\Attributes;

use Attribute;

/**
 * Prefixes the path of every route in a controller.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Prefix
{
    public function __construct(
        public string $path,
    ) {}
}

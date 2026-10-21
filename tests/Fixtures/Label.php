<?php

declare(strict_types=1);

namespace Refract\Tests\Fixtures;

use Attribute;

#[Attribute(Attribute::TARGET_ALL | Attribute::IS_REPEATABLE)]
final readonly class Label
{
    public function __construct(
        public string $value,
    ) {}
}

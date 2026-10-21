<?php

declare(strict_types=1);

namespace Refract\Tests\Fixtures;

use Generator;

/** Greets one or more people. */
#[Tag]
function greet(#[Tag] string $name, string $greeting = 'Hello', string ...$others): string
{
    return implode(', ', [$greeting, $name, ...$others]);
}

function &counter(): int
{
    static $count = 0;
    $count = is_int($count) ? $count : 0;

    return $count;
}

/**
 * @return Generator<int, int>
 */
function numbers(): Generator
{
    yield 1;
}

<?php

declare(strict_types=1);

namespace Refract\Tests\Fixtures\Php84;

/**
 * Covers PHP 8.4 property features: hooks, virtual properties, asymmetric visibility,
 * and final and abstract properties.
 *
 * Requires PHP 8.4 to parse, so it is excluded from PHPStan analysis and Pint,
 * and only loaded by tests skipped on older versions.
 */
abstract class Hooked
{
    public string $fullName {
        get => trim($this->first.' '.$this->last);
    }

    public string $first = '' {
        set => trim($value);
    }

    public private(set) string $last = '';

    public protected(set) int $count = 0;

    final public string $locked = 'locked';

    abstract public string $label { get; }
}

<?php

declare(strict_types=1);

namespace Refract\Benchmarks;

use PhpBench\Attributes as Bench;
use ReflectionProperty;
use Refract\ReflectedProperty;
use Refract\Tests\Fixtures\User;

/**
 * One property by class name, and a lookup that misses, as tryFrom() sees with untrusted input.
 */
#[Bench\Groups(['property-lookup'])]
final class PropertyLookupBench
{
    public function benchNative(): void
    {
        new ReflectionProperty(User::class, 'email');
    }

    public function benchRefract(): void
    {
        ReflectedProperty::from(User::class, 'email');
    }

    public function benchRefractMissing(): void
    {
        ReflectedProperty::tryFrom(User::class, 'missing');
    }
}

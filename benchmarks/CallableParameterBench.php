<?php

declare(strict_types=1);

namespace Refract\Benchmarks;

use PhpBench\Attributes as Bench;
use ReflectionParameter;
use Refract\ReflectedParameter;
use Refract\Tests\Fixtures\Signatures;

/**
 * One parameter of a [class, method] callable. Refract resolves the callable form and validates
 * the array shape first, then goes through the class and the method.
 */
#[Bench\Groups(['callable-parameter'])]
final class CallableParameterBench
{
    public function benchNative(): void
    {
        new ReflectionParameter([Signatures::class, 'send'], 'to');
    }

    public function benchRefract(): void
    {
        ReflectedParameter::from([Signatures::class, 'send'], 'to');
    }
}

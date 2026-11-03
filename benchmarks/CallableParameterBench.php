<?php

declare(strict_types=1);

namespace Refract\Benchmarks;

use PhpBench\Attributes as Bench;
use ReflectionParameter;
use Refract\ReflectedParameter;
use Refract\Tests\Fixtures\Signatures;

/**
 * One parameter of a [class, method] callable. Native builds that one parameter; Refract resolves the
 * callable through ReflectedCallable, wraps the method, then finds the parameter among all of them.
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

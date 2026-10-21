<?php

declare(strict_types=1);

namespace Refract\Benchmarks;

use PhpBench\Attributes as Bench;
use ReflectionClass;
use Refract\ReflectedClass;
use Refract\Tests\Fixtures\Article;

/**
 * The cost of the entry point itself: one wrapper object around the native reflection.
 */
#[Bench\Groups(['reflect-class'])]
final class ReflectClassBench
{
    public function benchNative(): void
    {
        new ReflectionClass(Article::class);
    }

    public function benchRefract(): void
    {
        ReflectedClass::from(Article::class);
    }
}

<?php

declare(strict_types=1);

namespace Refract\Benchmarks;

use PhpBench\Attributes as Bench;
use ReflectionAttribute;
use ReflectionMethod;
use Refract\ReflectedMethod;
use Refract\Tests\Fixtures\Article;
use Refract\Tests\Fixtures\Label;

/**
 * Creating the attribute objects of a method. Refract adds the method wrapper and the attribute wrappers
 * around the native objects.
 */
#[Bench\Groups(['attribute-instances'])]
final class AttributeInstancesBench
{
    public function benchNative(): void
    {
        $instances = [];

        foreach ((new ReflectionMethod(Article::class, 'render'))->getAttributes(Label::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            $instances[] = $attribute->newInstance();
        }
    }

    public function benchRefract(): void
    {
        ReflectedMethod::from(Article::class, 'render')->attributeInstances(Label::class);
    }
}

<?php

declare(strict_types=1);

namespace Refract\Benchmarks;

use PhpBench\Attributes as Bench;
use ReflectionAttribute;
use ReflectionClass;
use Refract\ReflectedClass;
use Refract\Tests\Fixtures\Article;
use Refract\Tests\Fixtures\Tag;

/**
 * The names of the public methods with a given attribute: a typical scan for routes or commands.
 * Refract builds a wrapper per method and a new collection per filter.
 */
#[Bench\Groups(['filter-methods'])]
final class FilterMethodsBench
{
    public function benchNative(): void
    {
        $names = [];

        foreach ((new ReflectionClass(Article::class))->getMethods() as $method) {
            if ($method->isPublic() && $method->getAttributes(Tag::class, ReflectionAttribute::IS_INSTANCEOF) !== []) {
                $names[] = $method->getName();
            }
        }
    }

    public function benchRefract(): void
    {
        ReflectedClass::from(Article::class)->methods()->public()->withAttribute(Tag::class)->names();
    }
}

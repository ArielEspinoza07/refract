<?php

declare(strict_types=1);

namespace Refract\Benchmarks;

use PhpBench\Attributes as Bench;
use ReflectionClass;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;
use Refract\Examples\Container\Newsletter;
use Refract\ReflectedClass;
use Refract\ReflectedParameter;

/**
 * The classes each constructor parameter needs: what a dependency injection container reads.
 * The native version handles only named types; Refract also flattens unions and resolves self and static.
 */
#[Bench\Groups(['constructor-dependencies'])]
final class ConstructorDependenciesBench
{
    public function benchNative(): void
    {
        $classes = [];

        foreach ((new ReflectionClass(Newsletter::class))->getConstructor()?->getParameters() ?? [] as $parameter) {
            $classes[$parameter->getName()] = self::nativeClassNames($parameter);
        }
    }

    public function benchRefract(): void
    {
        ReflectedClass::from(Newsletter::class)->constructor()?->parameters()->map(
            static fn (ReflectedParameter $parameter): array => $parameter->type()?->classNames() ?? [],
        );
    }

    /**
     * @return list<string>
     */
    private static function nativeClassNames(ReflectionParameter $parameter): array
    {
        $type = $parameter->getType();
        $types = $type instanceof ReflectionUnionType || $type instanceof ReflectionIntersectionType ? $type->getTypes() : [$type];
        $names = [];

        foreach ($types as $member) {
            if ($member instanceof ReflectionNamedType && ! $member->isBuiltin()) {
                $names[] = $member->getName();
            }
        }

        return $names;
    }
}

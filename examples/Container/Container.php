<?php

declare(strict_types=1);

namespace Refract\Examples\Container;

use Refract\ReflectedClass;
use Refract\ReflectedParameter;

/**
 * A minimal dependency injection container: it builds objects by reading their constructor.
 *
 * Each constructor parameter is resolved, in order, from:
 * 1. its class type, built recursively (interfaces through bind());
 * 2. its default value;
 * 3. null, when its type allows it.
 */
final class Container
{
    /** @var array<class-string, class-string> */
    private array $bindings = [];

    /**
     * The classes currently being built, in order, to detect circular dependencies.
     *
     * @var list<class-string>
     */
    private array $building = [];

    /**
     * @template T of object
     *
     * @param  class-string<T>  $abstract
     * @param  class-string<T>  $concrete
     */
    public function bind(string $abstract, string $concrete): void
    {
        $this->bindings[$abstract] = $concrete;
    }

    /**
     * @template T of object
     *
     * @param  class-string<T>  $class
     * @return T
     *
     * @throws UnresolvableDependency
     */
    public function make(string $class): object
    {
        if (in_array($class, $this->building, true)) {
            throw UnresolvableDependency::circular([...$this->building, $class]);
        }

        $concrete = ReflectedClass::from($this->bindings[$class] ?? $class);

        if (! $concrete->isInstantiable()) {
            throw UnresolvableDependency::notInstantiable($concrete->name());
        }

        $this->building[] = $class;

        try {
            $arguments = $concrete->constructor()?->parameters()->map($this->resolve(...)) ?? [];
        } finally {
            array_pop($this->building);
        }

        $instance = $concrete->newInstance(...$arguments);

        // Narrows the instance to T for static analysis: a binding could point to an unrelated class.
        return ReflectedClass::from($class)->isInstance($instance)
            ? $instance
            : throw UnresolvableDependency::wrongBinding($class, $concrete->name());
    }

    /**
     * @throws UnresolvableDependency
     */
    private function resolve(ReflectedParameter $parameter): mixed
    {
        $classNames = $parameter->type()?->classNames() ?? [];

        // classNames() returns names as declared, without checking they exist; tryFrom() checks them.
        $class = count($classNames) === 1 ? ReflectedClass::tryFrom($classNames[0]) : null;

        return match (true) {
            $class !== null && $this->canBuild($class) => $this->make($class->name()),
            $parameter->hasDefaultValue() => $parameter->defaultValue(),
            $parameter->allowsNull() => null,
            default => throw UnresolvableDependency::parameter($parameter),
        };
    }

    /**
     * @param  ReflectedClass<object>  $class
     */
    private function canBuild(ReflectedClass $class): bool
    {
        return isset($this->bindings[$class->name()]) || $class->isInstantiable();
    }
}

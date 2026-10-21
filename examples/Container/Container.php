<?php

declare(strict_types=1);

namespace Refract\Examples\Container;

use Closure;
use Refract\ReflectedCallable;
use Refract\ReflectedClass;
use Refract\ReflectedFunction;
use Refract\ReflectedMethod;
use Refract\ReflectedParameter;

/**
 * A minimal dependency injection container: it builds objects by reading their constructor, and calls any
 * callable by reading its parameters.
 *
 * Each parameter is resolved, in order, from:
 * 1. its class type, built recursively (interfaces through bind());
 * 2. its default value;
 * 3. null, when its type allows it.
 *
 * @phpstan-import-type CallableReference from ReflectedCallable
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
     * Calls any callable, resolving its parameters the same way as constructor parameters.
     *
     * A method given by class name, as [Handler::class, 'method'] or "Handler::method", is called on an
     * instance built by the container, unless it is static.
     *
     * @param  CallableReference  $callable
     *
     * @throws UnresolvableDependency
     */
    public function call(object|string|array $callable): mixed
    {
        $function = ReflectedCallable::from($callable);
        $arguments = $function->parameters()->map($this->resolve(...));

        return match (true) {
            $callable instanceof Closure => $callable(...$arguments),
            $function instanceof ReflectedFunction => $function->invoke(...$arguments),
            $function instanceof ReflectedMethod => $function->invoke($function->isStatic() ? null : $this->instanceFor($callable), ...$arguments),
            default => throw UnresolvableDependency::notCallable($function->qualifiedName()),
        };
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
     * The object to call a method on: the one given, or one built from the class name.
     *
     * @param  object|string|array{0: class-string|object, 1: string}  $callable
     *
     * @throws UnresolvableDependency
     */
    private function instanceFor(object|string|array $callable): object
    {
        if (is_object($callable)) {
            return $callable;
        }

        $class = is_array($callable) ? $callable[0] : explode('::', $callable, 2)[0];

        return is_object($class)
            ? $class
            : $this->make((ReflectedClass::tryFrom($class) ?? throw UnresolvableDependency::notInstantiable($class))->name());
    }

    /**
     * @param  ReflectedClass<object>  $class
     */
    private function canBuild(ReflectedClass $class): bool
    {
        return isset($this->bindings[$class->name()]) || $class->isInstantiable();
    }
}

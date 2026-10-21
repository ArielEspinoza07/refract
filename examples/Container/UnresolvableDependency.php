<?php

declare(strict_types=1);

namespace Refract\Examples\Container;

use LogicException;
use Refract\ReflectedParameter;

final class UnresolvableDependency extends LogicException
{
    public static function notInstantiable(string $class): self
    {
        return new self("Cannot build [{$class}]: it is abstract or an interface without a binding.");
    }

    /**
     * @param  list<string>  $chain  The classes being built, ending with the one that closes the circle.
     */
    public static function circular(array $chain): self
    {
        return new self('Cannot build ['.end($chain).']: circular dependency '.implode(' -> ', $chain).'.');
    }

    public static function wrongBinding(string $abstract, string $concrete): self
    {
        return new self("Cannot build [{$abstract}]: it is bound to [{$concrete}], which does not extend or implement it.");
    }

    public static function parameter(ReflectedParameter $parameter): self
    {
        return new self("Cannot resolve [{$parameter->qualifiedName()}]: it has no class type, no default value, and is not nullable.");
    }
}

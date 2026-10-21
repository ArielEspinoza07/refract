<?php

declare(strict_types=1);

namespace Refract\Collections;

use Refract\ReflectedParameter;

/**
 * @extends Collection<ReflectedParameter>
 */
final readonly class ParameterCollection extends Collection
{
    /**
     * Keep only the parameters a caller must pass.
     */
    public function required(): self
    {
        return $this->filter(static fn (ReflectedParameter $parameter): bool => ! $parameter->isOptional());
    }

    /**
     * Keep only the parameters a caller may leave out: those with a default value, and variadics.
     */
    public function optional(): self
    {
        return $this->filter(static fn (ReflectedParameter $parameter): bool => $parameter->isOptional());
    }

    public function variadic(): self
    {
        return $this->filter(static fn (ReflectedParameter $parameter): bool => $parameter->isVariadic());
    }

    /**
     * Keep only constructor parameters that also declare a property.
     */
    public function promoted(): self
    {
        return $this->filter(static fn (ReflectedParameter $parameter): bool => $parameter->isPromoted());
    }

    /**
     * Keep only the parameters that have the given attribute, or an attribute that extends it.
     *
     * @param  class-string  $attribute
     */
    public function withAttribute(string $attribute): self
    {
        return $this->filter(static fn (ReflectedParameter $parameter): bool => $parameter->hasAttribute($attribute));
    }
}

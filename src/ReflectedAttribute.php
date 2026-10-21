<?php

declare(strict_types=1);

namespace Refract;

use Error;
use ReflectionAttribute;
use Refract\Collections\Collection;
use Refract\Concerns\PreventsSerialization;
use Refract\Contracts\Named;
use Refract\Enums\AttributeTarget;
use Refract\Exceptions\AttributeNotFound;
use Refract\Exceptions\AttributeNotInstantiable;
use Refract\Exceptions\ClassNotFound;

/**
 * Fluent, immutable reflection of an attribute declaration.
 *
 * Reflecting an attribute never loads or validates its class; only newInstance() does.
 *
 * @template T of object
 */
final readonly class ReflectedAttribute implements Named
{
    use PreventsSerialization;

    /**
     * @param  ReflectionAttribute<T>  $native
     */
    private function __construct(
        private ReflectionAttribute $native,
    ) {}

    /**
     * Reflect the first attribute of the given type (or a type that extends it) declared on a class.
     *
     * Attributes on methods, properties and constants are reached through those reflections instead.
     *
     * @template TAttribute of object
     *
     * @param  class-string|object  $class
     * @param  class-string<TAttribute>  $attribute
     * @return self<TAttribute>
     *
     * @throws ClassNotFound When the given name is not a class, interface, trait or enum.
     * @throws AttributeNotFound When the class does not declare the attribute.
     */
    public static function from(string|object $class, string $attribute): self
    {
        return ReflectedClass::from($class)->attribute($attribute);
    }

    /**
     * Like from(), but returns null instead of throwing when the class does not exist
     * or does not declare the attribute.
     *
     * Accepts any string as class name, so it can be used directly on untrusted input.
     *
     * @template TAttribute of object
     *
     * @param  class-string<TAttribute>  $attribute
     * @return self<TAttribute>|null
     */
    public static function tryFrom(string|object $class, string $attribute): ?self
    {
        $reflected = ReflectedClass::tryFrom($class);

        if ($reflected === null || ! $reflected->hasAttribute($attribute)) {
            return null;
        }

        return $reflected->attribute($attribute);
    }

    /**
     * Reflect all attributes declared on a class, optionally only those of the given type
     * or a type that extends it. Returns an empty collection when there are none.
     *
     * Attributes on methods, properties and constants are reached through those reflections instead.
     *
     * @template TAttribute of object
     *
     * @param  class-string|object  $class
     * @param  class-string<TAttribute>|null  $attribute
     * @return ($attribute is null ? Collection<self<object>> : Collection<self<TAttribute>>)
     *
     * @throws ClassNotFound When the given name is not a class, interface, trait or enum.
     */
    public static function collectFrom(string|object $class, ?string $attribute = null): Collection
    {
        return ReflectedClass::from($class)->attributes($attribute);
    }

    /**
     * @template TAttribute of object
     *
     * @param  ReflectionAttribute<TAttribute>  $native
     * @return self<TAttribute>
     */
    public static function fromNative(ReflectionAttribute $native): self
    {
        return new self($native);
    }

    /**
     * @return ReflectionAttribute<T>
     */
    public function native(): ReflectionAttribute
    {
        return $this->native;
    }

    /**
     * The attribute class name, as written in the declaration.
     */
    public function name(): string
    {
        return $this->native->getName();
    }

    /**
     * The arguments exactly as written in the declaration: positional arguments under
     * integer keys, named arguments under their name.
     *
     * @return array<int|string, mixed>
     */
    public function arguments(): array
    {
        return $this->native->getArguments();
    }

    /**
     * The kind of declaration the attribute is placed on.
     */
    public function target(): AttributeTarget
    {
        return AttributeTarget::from($this->native->getTarget());
    }

    /**
     * Whether the same attribute appears more than once on the same declaration.
     */
    public function isRepeated(): bool
    {
        return $this->native->isRepeated();
    }

    /**
     * Create the attribute object, passing the declared arguments to its constructor.
     *
     * Exceptions thrown by the attribute's own constructor are not caught.
     *
     * @return T
     *
     * @throws AttributeNotInstantiable When the attribute class does not exist, is not declared
     *                                  as an attribute, does not allow this target or repetition,
     *                                  or the arguments do not match its constructor.
     */
    public function newInstance(): object
    {
        try {
            return $this->native->newInstance();
        } catch (Error $error) {
            throw AttributeNotInstantiable::named($this->name(), $error);
        }
    }
}

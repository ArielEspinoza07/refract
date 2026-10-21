<?php

declare(strict_types=1);

namespace Refract\Concerns;

use ReflectionAttribute;
use Refract\Collections\Collection;
use Refract\Contracts\HasAttributes;
use Refract\Exceptions\AttributeNotFound;
use Refract\Exceptions\AttributeNotInstantiable;
use Refract\ReflectedAttribute;

/**
 * The single implementation of HasAttributes, shared by every type whose native
 * reflection exposes getAttributes().
 *
 * @internal
 *
 * @see HasAttributes
 */
trait InteractsWithAttributes
{
    /**
     * @template TAttribute of object
     *
     * @param  class-string<TAttribute>|null  $attribute
     * @return ($attribute is null ? Collection<ReflectedAttribute<object>> : Collection<ReflectedAttribute<TAttribute>>)
     */
    public function attributes(?string $attribute = null): Collection
    {
        return new Collection(array_map(ReflectedAttribute::fromNative(...), $this->nativeAttributes($attribute)));
    }

    /**
     * @param  class-string  $attribute
     */
    public function hasAttribute(string $attribute): bool
    {
        return $this->nativeAttributes($attribute) !== [];
    }

    /**
     * @template TAttribute of object
     *
     * @param  class-string<TAttribute>  $attribute
     * @return ReflectedAttribute<TAttribute>
     *
     * @throws AttributeNotFound
     */
    public function attribute(string $attribute): ReflectedAttribute
    {
        return $this->attributes($attribute)->first()
            ?? throw AttributeNotFound::on($this->qualifiedName(), $attribute);
    }

    /**
     * @template TAttribute of object
     *
     * @param  class-string<TAttribute>  $attribute
     * @return list<TAttribute>
     *
     * @throws AttributeNotInstantiable
     */
    public function attributeInstances(string $attribute): array
    {
        $instances = [];

        foreach ($this->attributes($attribute) as $reflected) {
            $instances[] = $reflected->newInstance();
        }

        return $instances;
    }

    /**
     * The fully qualified name of the declaration, also used in error messages.
     */
    abstract public function qualifiedName(): string;

    /**
     * The single place that decides which attributes match: all of them, or those of the given type
     * or a type that extends it.
     *
     * @template TAttribute of object
     *
     * @param  class-string<TAttribute>|null  $attribute
     * @return ($attribute is null ? list<ReflectionAttribute<object>> : list<ReflectionAttribute<TAttribute>>)
     */
    private function nativeAttributes(?string $attribute): array
    {
        return $attribute === null
            ? $this->native()->getAttributes()
            : $this->native()->getAttributes($attribute, ReflectionAttribute::IS_INSTANCEOF);
    }
}

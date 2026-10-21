<?php

declare(strict_types=1);

namespace Refract\Contracts;

use Refract\Collections\Collection;
use Refract\Exceptions\AttributeNotFound;
use Refract\ReflectedAttribute;

/**
 * A reflected declaration that can carry attributes: a class, method, property or constant.
 *
 * Not meant to be implemented outside Refract. New methods may be added in minor releases.
 */
interface HasAttributes
{
    /**
     * Attributes on the declaration, optionally only those of the given type or a type that extends it.
     *
     * @template TAttribute of object
     *
     * @param  class-string<TAttribute>|null  $attribute
     * @return ($attribute is null ? Collection<ReflectedAttribute<object>> : Collection<ReflectedAttribute<TAttribute>>)
     */
    public function attributes(?string $attribute = null): Collection;

    /**
     * Whether the declaration has the given attribute, or an attribute that extends it.
     *
     * @param  class-string  $attribute
     */
    public function hasAttribute(string $attribute): bool;

    /**
     * The first attribute of the given type, or of a type that extends it.
     * For repeatable attributes, use attributes() to get all of them.
     *
     * @template TAttribute of object
     *
     * @param  class-string<TAttribute>  $attribute
     * @return ReflectedAttribute<TAttribute>
     *
     * @throws AttributeNotFound When the declaration does not have the attribute.
     */
    public function attribute(string $attribute): ReflectedAttribute;
}

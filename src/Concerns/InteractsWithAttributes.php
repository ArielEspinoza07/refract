<?php

declare(strict_types=1);

namespace Refract\Concerns;

use ReflectionAttribute;
use Refract\Collections\Collection;
use Refract\Contracts\HasAttributes;
use Refract\Exceptions\AttributeNotFound;
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
        $attributes = $attribute === null
            ? $this->native()->getAttributes()
            : $this->native()->getAttributes($attribute, ReflectionAttribute::IS_INSTANCEOF);

        return new Collection(array_map(ReflectedAttribute::fromNative(...), $attributes));
    }

    /**
     * @param  class-string  $attribute
     */
    public function hasAttribute(string $attribute): bool
    {
        return ! $this->attributes($attribute)->isEmpty();
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
            ?? throw AttributeNotFound::on($this->declarationName(), $attribute);
    }

    /**
     * How the declaration is named in error messages, e.g. "App\User" or "App\User::save()".
     */
    abstract private function declarationName(): string;
}

<?php

declare(strict_types=1);

namespace Refract\Concerns;

use Refract\Contracts\ClassMember;
use Refract\ReflectedClass;

/**
 * The single implementation of ClassMember's own methods, shared by every type whose native
 * reflection belongs to a class: methods, properties and constants.
 *
 * @internal
 *
 * @see ClassMember
 */
trait BelongsToClass
{
    public function isPublic(): bool
    {
        return $this->native()->isPublic();
    }

    public function isProtected(): bool
    {
        return $this->native()->isProtected();
    }

    public function isPrivate(): bool
    {
        return $this->native()->isPrivate();
    }

    /**
     * @return ReflectedClass<object>
     */
    public function declaringClass(): ReflectedClass
    {
        return ReflectedClass::fromNative($this->native()->getDeclaringClass());
    }

    /**
     * @param  class-string  $class
     */
    public function isDeclaredBy(string $class): bool
    {
        return $this->native()->class === $class;
    }
}

<?php

declare(strict_types=1);

namespace Refract\Contracts;

use Refract\ReflectedClass;

/**
 * A reflected member of a class: a method, property or constant.
 *
 * Not meant to be implemented outside Refract. New methods may be added in minor releases.
 */
interface ClassMember extends HasAttributes, Named
{
    public function isPublic(): bool;

    public function isProtected(): bool;

    public function isPrivate(): bool;

    /**
     * The class that declares the member. Members imported from a trait are declared
     * by the class that uses the trait.
     *
     * @return ReflectedClass<object>
     */
    public function declaringClass(): ReflectedClass;

    /**
     * Whether the member is declared by the given class, rather than inherited from a parent.
     *
     * @param  class-string  $class
     */
    public function isDeclaredBy(string $class): bool;

    /**
     * The fully qualified name, e.g. "App\Models\User::save()", "App\Models\User::$email" or "App\Models\User::TABLE".
     */
    public function qualifiedName(): string;
}

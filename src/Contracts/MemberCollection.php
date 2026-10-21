<?php

declare(strict_types=1);

namespace Refract\Contracts;

/**
 * Filters shared by every collection of class members: methods, properties and constants.
 *
 * Every filter returns a new collection of the same type; the original is never modified.
 *
 * Not meant to be implemented outside Refract. New methods may be added in minor releases.
 */
interface MemberCollection
{
    public function public(): static;

    public function protected(): static;

    public function private(): static;

    /**
     * Keep only the members declared by the given class. Members imported from a trait
     * count as declared by the class that uses the trait.
     *
     * @param  class-string  $class
     */
    public function declaredBy(string $class): static;

    /**
     * Keep only the members that have the given attribute, or an attribute that extends it.
     *
     * @param  class-string  $attribute
     */
    public function withAttribute(string $attribute): static;
}

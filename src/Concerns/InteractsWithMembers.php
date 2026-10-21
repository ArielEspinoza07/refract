<?php

declare(strict_types=1);

namespace Refract\Concerns;

use Refract\Contracts\ClassMember;
use Refract\Contracts\MemberCollection;

/**
 * The single implementation of MemberCollection, shared by every collection of class members.
 *
 * @internal
 *
 * @see MemberCollection
 */
trait InteractsWithMembers
{
    public function public(): static
    {
        return $this->filter(static fn (ClassMember $member): bool => $member->isPublic());
    }

    public function protected(): static
    {
        return $this->filter(static fn (ClassMember $member): bool => $member->isProtected());
    }

    public function private(): static
    {
        return $this->filter(static fn (ClassMember $member): bool => $member->isPrivate());
    }

    /**
     * @param  class-string  $class
     */
    public function declaredBy(string $class): static
    {
        return $this->filter(static fn (ClassMember $member): bool => $member->isDeclaredBy($class));
    }

    /**
     * @param  class-string  $attribute
     */
    public function withAttribute(string $attribute): static
    {
        return $this->filter(static fn (ClassMember $member): bool => $member->hasAttribute($attribute));
    }
}

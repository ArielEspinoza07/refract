<?php

declare(strict_types=1);

namespace Refract\Collections;

use Refract\Concerns\InteractsWithMembers;
use Refract\Contracts\MemberCollection;
use Refract\ReflectedProperty;

/**
 * @extends Collection<ReflectedProperty>
 */
final readonly class PropertyCollection extends Collection implements MemberCollection
{
    use InteractsWithMembers;

    public function static(): self
    {
        return $this->filter(static fn (ReflectedProperty $property): bool => $property->isStatic());
    }

    public function readonly(): self
    {
        return $this->filter(static fn (ReflectedProperty $property): bool => $property->isReadonly());
    }

    /**
     * Keep only the properties declared through constructor property promotion.
     */
    public function promoted(): self
    {
        return $this->filter(static fn (ReflectedProperty $property): bool => $property->isPromoted());
    }
}

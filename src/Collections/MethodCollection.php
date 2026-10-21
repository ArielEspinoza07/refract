<?php

declare(strict_types=1);

namespace Refract\Collections;

use Refract\Concerns\InteractsWithMembers;
use Refract\Contracts\MemberCollection;
use Refract\ReflectedMethod;

/**
 * @extends Collection<ReflectedMethod>
 */
final readonly class MethodCollection extends Collection implements MemberCollection
{
    use InteractsWithMembers;

    public function static(): self
    {
        return $this->filter(static fn (ReflectedMethod $method): bool => $method->isStatic());
    }

    public function abstract(): self
    {
        return $this->filter(static fn (ReflectedMethod $method): bool => $method->isAbstract());
    }

    public function final(): self
    {
        return $this->filter(static fn (ReflectedMethod $method): bool => $method->isFinal());
    }
}

<?php

declare(strict_types=1);

namespace Refract\Collections;

use Refract\Concerns\InteractsWithMembers;
use Refract\Contracts\MemberCollection;
use Refract\ReflectedConstant;

/**
 * @extends Collection<ReflectedConstant>
 */
final readonly class ConstantCollection extends Collection implements MemberCollection
{
    use InteractsWithMembers;

    public function final(): self
    {
        return $this->filter(static fn (ReflectedConstant $constant): bool => $constant->isFinal());
    }
}

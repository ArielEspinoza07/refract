<?php

declare(strict_types=1);

namespace Refract\Concerns;

use Refract\ReflectedType;

/**
 * The single implementation of type() for typed declarations: properties, constants and parameters.
 *
 * @internal
 */
trait InteractsWithTypes
{
    /**
     * The declared type, or null when there is none.
     */
    public function type(): ?ReflectedType
    {
        $type = $this->native()->getType();

        return $type === null ? null : ReflectedType::fromNative($type, $this->typeScope());
    }

    /**
     * The class that self, static and parent refer to in the type, or null outside a class.
     *
     * @return class-string|null
     */
    abstract private function typeScope(): ?string;
}

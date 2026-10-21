<?php

declare(strict_types=1);

namespace Refract\Contracts;

/**
 * A reflection with a name. Implemented by every Refract reflection, so collections can list and key them by name.
 *
 * Not meant to be implemented outside Refract.
 */
interface Named
{
    public function name(): string;
}

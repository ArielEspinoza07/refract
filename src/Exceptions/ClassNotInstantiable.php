<?php

declare(strict_types=1);

namespace Refract\Exceptions;

use LogicException;
use Throwable;

final class ClassNotInstantiable extends LogicException implements RefractException
{
    public static function named(string $class, ?Throwable $previous = null): self
    {
        return new self("Class [{$class}] cannot be instantiated.", previous: $previous);
    }
}

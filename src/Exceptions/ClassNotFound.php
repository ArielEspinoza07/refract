<?php

declare(strict_types=1);

namespace Refract\Exceptions;

use InvalidArgumentException;
use Throwable;

final class ClassNotFound extends InvalidArgumentException implements RefractException
{
    public static function named(string $class, ?Throwable $previous = null): self
    {
        return new self("Class [{$class}] does not exist.", previous: $previous);
    }
}

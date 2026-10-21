<?php

declare(strict_types=1);

namespace Refract\Exceptions;

use InvalidArgumentException;
use Throwable;

final class PropertyNotFound extends InvalidArgumentException implements RefractException
{
    public static function in(string $class, string $property, ?Throwable $previous = null): self
    {
        return new self("Property [{$class}::\${$property}] does not exist.", previous: $previous);
    }
}

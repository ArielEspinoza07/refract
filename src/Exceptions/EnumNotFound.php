<?php

declare(strict_types=1);

namespace Refract\Exceptions;

use InvalidArgumentException;
use Throwable;

final class EnumNotFound extends InvalidArgumentException implements RefractException
{
    public static function named(string $enum, ?Throwable $previous = null): self
    {
        return new self("Enum [{$enum}] does not exist.", previous: $previous);
    }
}

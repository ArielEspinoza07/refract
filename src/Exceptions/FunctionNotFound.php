<?php

declare(strict_types=1);

namespace Refract\Exceptions;

use InvalidArgumentException;
use Throwable;

final class FunctionNotFound extends InvalidArgumentException implements RefractException
{
    public static function named(string $function, ?Throwable $previous = null): self
    {
        return new self("Function [{$function}] does not exist.", previous: $previous);
    }
}

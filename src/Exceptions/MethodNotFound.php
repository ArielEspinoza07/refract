<?php

declare(strict_types=1);

namespace Refract\Exceptions;

use InvalidArgumentException;

final class MethodNotFound extends InvalidArgumentException implements RefractException
{
    public static function in(string $class, string $method): self
    {
        return new self("Method [{$class}::{$method}()] does not exist.");
    }
}

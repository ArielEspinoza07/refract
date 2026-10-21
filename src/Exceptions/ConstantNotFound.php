<?php

declare(strict_types=1);

namespace Refract\Exceptions;

use InvalidArgumentException;

final class ConstantNotFound extends InvalidArgumentException implements RefractException
{
    public static function in(string $class, string $constant): self
    {
        return new self("Constant [{$class}::{$constant}] does not exist.");
    }
}

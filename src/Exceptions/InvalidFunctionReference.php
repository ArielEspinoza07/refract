<?php

declare(strict_types=1);

namespace Refract\Exceptions;

use InvalidArgumentException;

final class InvalidFunctionReference extends InvalidArgumentException implements RefractException
{
    public static function malformedArray(): self
    {
        return new self('A method reference array must be [class-or-object, method], with exactly two items.');
    }
}

<?php

declare(strict_types=1);

namespace Refract\Exceptions;

use LogicException;
use Throwable;

final class AttributeNotInstantiable extends LogicException implements RefractException
{
    public static function named(string $attribute, Throwable $previous): self
    {
        return new self("Attribute [{$attribute}] cannot be instantiated: {$previous->getMessage()}", previous: $previous);
    }
}

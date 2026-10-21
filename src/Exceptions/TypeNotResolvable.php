<?php

declare(strict_types=1);

namespace Refract\Exceptions;

use LogicException;

final class TypeNotResolvable extends LogicException implements RefractException
{
    public static function withoutScope(string $type): self
    {
        return new self("Type [{$type}] cannot be resolved without the class it is declared in.");
    }
}

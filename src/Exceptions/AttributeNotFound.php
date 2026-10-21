<?php

declare(strict_types=1);

namespace Refract\Exceptions;

use InvalidArgumentException;

final class AttributeNotFound extends InvalidArgumentException implements RefractException
{
    /**
     * @param  string  $target  The declaration, e.g. "App\User" or "App\User::save()".
     */
    public static function on(string $target, string $attribute): self
    {
        return new self("Attribute [{$attribute}] is not declared on [{$target}].");
    }
}

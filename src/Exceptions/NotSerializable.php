<?php

declare(strict_types=1);

namespace Refract\Exceptions;

use LogicException;

final class NotSerializable extends LogicException implements RefractException
{
    public static function type(string $class): self
    {
        return new self(
            "[{$class}] cannot be serialized, because it wraps a native reflection. "
            .'Store the name you reflected, and call from() again when you need the reflection.',
        );
    }
}

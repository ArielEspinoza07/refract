<?php

declare(strict_types=1);

namespace Refract\Exceptions;

use InvalidArgumentException;

final class InstanceRequired extends InvalidArgumentException implements RefractException
{
    public static function forProperty(string $target): self
    {
        return new self("Property [{$target}] is not static, so reading it requires an instance.");
    }

    public static function forMethod(string $target): self
    {
        return new self("Method [{$target}] is not static, so calling it requires an instance.");
    }
}

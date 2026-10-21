<?php

declare(strict_types=1);

namespace Refract\Exceptions;

use InvalidArgumentException;
use Refract\Enums\PropertyHook;

final class PropertyHookNotFound extends InvalidArgumentException implements RefractException
{
    public static function on(string $property, PropertyHook $hook): self
    {
        return new self("Property [{$property}] has no {$hook->value} hook.");
    }
}

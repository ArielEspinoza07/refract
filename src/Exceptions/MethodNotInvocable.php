<?php

declare(strict_types=1);

namespace Refract\Exceptions;

use LogicException;
use Throwable;

final class MethodNotInvocable extends LogicException implements RefractException
{
    public static function because(string $method, Throwable $previous): self
    {
        return new self("Method [{$method}] cannot be invoked: {$previous->getMessage()}", previous: $previous);
    }
}

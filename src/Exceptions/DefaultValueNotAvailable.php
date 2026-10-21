<?php

declare(strict_types=1);

namespace Refract\Exceptions;

use LogicException;

final class DefaultValueNotAvailable extends LogicException implements RefractException
{
    /**
     * @param  string  $target  The declaration, e.g. "App\User::$email" or "App\User::save($data)".
     */
    public static function for(string $target): self
    {
        return new self("[{$target}] has no default value.");
    }
}

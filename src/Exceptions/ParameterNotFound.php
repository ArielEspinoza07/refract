<?php

declare(strict_types=1);

namespace Refract\Exceptions;

use InvalidArgumentException;

final class ParameterNotFound extends InvalidArgumentException implements RefractException
{
    /**
     * @param  string  $function  The declaration, e.g. "App\User::save()" or "strlen()".
     * @param  string|int  $parameter  The parameter name, or its zero-based position.
     */
    public static function in(string $function, string|int $parameter): self
    {
        $label = is_int($parameter) ? "#{$parameter}" : "\${$parameter}";

        return new self("Parameter [{$label}] does not exist on [{$function}].");
    }
}

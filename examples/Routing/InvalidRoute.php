<?php

declare(strict_types=1);

namespace Refract\Examples\Routing;

use LogicException;

final class InvalidRoute extends LogicException
{
    public static function duplicate(RouteDefinition $route, RouteDefinition $existing): self
    {
        return new self(
            "[{$route->signature()}] is declared by both {$existing->controller}::{$existing->action}() "
            ."and {$route->controller}::{$route->action}().",
        );
    }

    public static function missingValue(string $parameter): self
    {
        return new self("No path value for \${$parameter}, and it has no default value.");
    }

    public static function invalidValue(string $parameter, string $value, string $type): self
    {
        return new self("Path value [{$value}] is not a valid {$type} for \${$parameter}.");
    }
}

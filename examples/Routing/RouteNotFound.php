<?php

declare(strict_types=1);

namespace Refract\Examples\Routing;

use RuntimeException;

final class RouteNotFound extends RuntimeException
{
    public static function for(HttpMethod $verb, string $path): self
    {
        return new self("No route matches [{$verb->value} {$path}].");
    }
}

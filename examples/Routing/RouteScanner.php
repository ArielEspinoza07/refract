<?php

declare(strict_types=1);

namespace Refract\Examples\Routing;

use Refract\Examples\Routing\Attributes\Prefix;
use Refract\Examples\Routing\Attributes\Route;
use Refract\ReflectedAttribute;
use Refract\ReflectedMethod;

/**
 * Builds route definitions from the #[Route] and #[Prefix] attributes of controllers.
 */
final class RouteScanner
{
    /**
     * @param  class-string  ...$controllers
     * @return list<RouteDefinition>
     *
     * @throws InvalidRoute When two routes have the same verb and path.
     */
    public function scan(string ...$controllers): array
    {
        $routes = [];

        foreach ($controllers as $controller) {
            $prefix = ReflectedAttribute::tryFrom($controller, Prefix::class)?->newInstance()->path ?? '';

            // Only public methods can handle requests; a #[Route] on any other method is ignored.
            foreach (ReflectedMethod::collectFrom($controller)->public()->withAttribute(Route::class) as $method) {
                foreach ($method->attributeInstances(Route::class) as $route) {
                    $definition = new RouteDefinition($route->verb, $prefix.$route->path, $controller, $method->name());

                    $routes[$definition->signature()] = isset($routes[$definition->signature()])
                        ? throw InvalidRoute::duplicate($definition, $routes[$definition->signature()])
                        : $definition;
                }
            }
        }

        return array_values($routes);
    }
}

<?php

declare(strict_types=1);

namespace Refract\Examples\Routing;

use Refract\ReflectedClass;
use Refract\ReflectedMethod;
use Refract\ReflectedParameter;

/**
 * Dispatches a request to the controller method of the matching route, passing path values to the
 * parameters with the same name, converted to the parameter's declared type.
 */
final readonly class Router
{
    /**
     * @param  list<RouteDefinition>  $routes
     */
    public function __construct(
        private array $routes,
    ) {}

    /**
     * @throws RouteNotFound When no route matches the verb and path.
     * @throws InvalidRoute When a path value cannot be passed to its parameter.
     */
    public function dispatch(HttpMethod $verb, string $path): mixed
    {
        foreach ($this->routes as $route) {
            $values = $route->match($verb, $path);

            if ($values !== null) {
                $action = ReflectedMethod::from($route->controller, $route->action);
                $arguments = $action->parameters()->keyByName();

                return $action->invoke(
                    ReflectedClass::from($route->controller)->newInstance(),
                    ...array_map(fn (ReflectedParameter $parameter): mixed => $this->argument($parameter, $values), $arguments),
                );
            }
        }

        throw RouteNotFound::for($verb, $path);
    }

    /**
     * @param  array<string, string>  $values
     *
     * @throws InvalidRoute
     */
    private function argument(ReflectedParameter $parameter, array $values): mixed
    {
        if (! array_key_exists($parameter->name(), $values)) {
            return $parameter->hasDefaultValue()
                ? $parameter->defaultValue()
                : throw InvalidRoute::missingValue($parameter->name());
        }

        $value = $values[$parameter->name()];
        $type = $parameter->type();

        // Path values are strings; convert them to what the parameter declares, then let the type confirm it.
        $converted = match ($type?->name()) {
            'int', '?int' => filter_var($value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE),
            'float', '?float' => filter_var($value, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE),
            default => $value,
        };

        return $type === null || $type->allows($converted)
            ? $converted
            : throw InvalidRoute::invalidValue($parameter->name(), $value, $type->name());
    }
}

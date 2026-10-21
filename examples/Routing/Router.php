<?php

declare(strict_types=1);

namespace Refract\Examples\Routing;

use Closure;
use Refract\Contracts\FunctionLike;
use Refract\ReflectedCallable;
use Refract\ReflectedClass;
use Refract\ReflectedMethod;
use Refract\ReflectedParameter;

/**
 * Dispatches a request to the handler of the matching route, passing path values to the parameters
 * with the same name, converted to the parameter's declared type.
 *
 * Handlers are controller methods, usually found by RouteScanner, or closures registered with add().
 */
final class Router
{
    /** @var array<string, RouteDefinition> */
    private array $routes = [];

    /**
     * @param  list<RouteDefinition>  $routes
     *
     * @throws InvalidRoute When two routes have the same verb and path.
     */
    public function __construct(array $routes = [])
    {
        foreach ($routes as $route) {
            $this->register($route);
        }
    }

    /**
     * @throws InvalidRoute When a route with the same verb and path exists.
     */
    public function add(HttpMethod $verb, string $path, Closure $handler): void
    {
        $this->register(new RouteDefinition($verb, $path, $handler));
    }

    /**
     * @return list<RouteDefinition>
     */
    public function routes(): array
    {
        return array_values($this->routes);
    }

    /**
     * @throws RouteNotFound When no route matches the verb and path.
     * @throws InvalidRoute When a path value cannot be passed to its parameter.
     */
    public function dispatch(HttpMethod $verb, string $path): mixed
    {
        foreach ($this->routes as $route) {
            $values = $route->match($verb, $path);

            if ($values !== null) {
                $handler = ReflectedCallable::from($route->handler);
                $arguments = array_map(
                    fn (ReflectedParameter $parameter): mixed => $this->argument($parameter, $values),
                    $handler->parameters()->keyByName(),
                );

                return $this->call($route->handler, $handler, $arguments);
            }
        }

        throw RouteNotFound::for($verb, $path);
    }

    /**
     * @throws InvalidRoute
     */
    private function register(RouteDefinition $route): void
    {
        $existing = $this->routes[$route->signature()] ?? null;

        $this->routes[$route->signature()] = $existing === null ? $route : throw InvalidRoute::duplicate($route, $existing);
    }

    /**
     * @param  Closure|array{0: class-string, 1: string}  $callable
     * @param  array<string, mixed>  $arguments
     */
    private function call(Closure|array $callable, FunctionLike $handler, array $arguments): mixed
    {
        if ($callable instanceof Closure) {
            return $callable(...$arguments);
        }

        $method = $handler instanceof ReflectedMethod ? $handler : ReflectedMethod::from($callable[0], $callable[1]);

        return $method->invoke($method->isStatic() ? null : ReflectedClass::from($callable[0])->newInstance(), ...$arguments);
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
        $converted = match ($type?->nonNullName()) {
            'int' => filter_var($value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE),
            'float' => filter_var($value, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE),
            default => $value,
        };

        return $type === null || $type->allows($converted)
            ? $converted
            : throw InvalidRoute::invalidValue($parameter->name(), $value, $type->name());
    }
}

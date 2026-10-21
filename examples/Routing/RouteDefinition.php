<?php

declare(strict_types=1);

namespace Refract\Examples\Routing;

use Closure;
use Refract\ReflectedCallable;

/**
 * One route: which handler answers a verb and path. The handler is a controller method or a closure.
 */
final readonly class RouteDefinition
{
    /**
     * @param  Closure|array{0: class-string, 1: string}  $handler
     */
    public function __construct(
        public HttpMethod $verb,
        public string $path,
        public Closure|array $handler,
    ) {}

    /**
     * The values of the path placeholders when the request matches, keyed by name; null otherwise.
     *
     * @return array<string, string>|null
     */
    public function match(HttpMethod $verb, string $path): ?array
    {
        $pattern = '#^'.preg_replace('#\\\\\{(\w+)\\\\\}#', '(?P<$1>[^/]+)', preg_quote($this->path, '#')).'$#';

        if ($verb !== $this->verb || preg_match($pattern, $path, $matches) !== 1) {
            return null;
        }

        return array_filter($matches, is_string(...), ARRAY_FILTER_USE_KEY);
    }

    public function signature(): string
    {
        return "{$this->verb->value} {$this->path}";
    }

    /**
     * The handler's full name, e.g. "App\UserController::show()", or the name PHP gives a closure.
     */
    public function handlerName(): string
    {
        return ReflectedCallable::from($this->handler)->qualifiedName();
    }
}

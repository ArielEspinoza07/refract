<?php

declare(strict_types=1);

namespace Refract\Examples\Routing;

/**
 * One route: which controller method handles a verb and path.
 */
final readonly class RouteDefinition
{
    /**
     * @param  class-string  $controller
     */
    public function __construct(
        public HttpMethod $verb,
        public string $path,
        public string $controller,
        public string $action,
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
}

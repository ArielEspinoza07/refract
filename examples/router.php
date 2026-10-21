<?php

declare(strict_types=1);

/*
 * Builds a route table from #[Route] attributes, adds closure routes, then dispatches requests.
 * Run it with: php examples/router.php
 */

use Refract\Examples\Routing\Controllers\UserController;
use Refract\Examples\Routing\HttpMethod;
use Refract\Examples\Routing\InvalidRoute;
use Refract\Examples\Routing\Router;
use Refract\Examples\Routing\RouteNotFound;
use Refract\Examples\Routing\RouteScanner;

require __DIR__.'/../vendor/autoload.php';

$router = new Router((new RouteScanner())->scan(UserController::class));
$router->add(HttpMethod::Get, '/health', static fn (): string => 'OK');
$router->add(HttpMethod::Get, '/hello/{name}', static fn (string $name): string => "Hello, {$name}!");
$router->add(HttpMethod::Get, '/sum/{a}/{b}', static fn (int $a, int $b = 0): string => (string) ($a + $b));

echo "Routes:\n";

foreach ($router->routes() as $route) {
    printf("  %-6s %-24s %s\n", $route->verb->value, $route->path, $route->handlerName());
}

echo "\nRequests:\n";

$requests = [
    [HttpMethod::Get, '/users'],
    [HttpMethod::Get, '/users/42'],
    [HttpMethod::Get, '/users/42/posts'],
    [HttpMethod::Get, '/users/42/posts/3'],
    [HttpMethod::Post, '/users/import'],
    [HttpMethod::Get, '/health'],
    [HttpMethod::Get, '/hello/ada'],
    [HttpMethod::Get, '/sum/2/40'],
    [HttpMethod::Get, '/users/abc'],
    [HttpMethod::Get, '/sum/2/x'],
    [HttpMethod::Delete, '/users/42'],
];

foreach ($requests as [$verb, $path]) {
    try {
        $response = $router->dispatch($verb, $path);
        $output = is_string($response) ? $response : get_debug_type($response);
    } catch (InvalidRoute|RouteNotFound $exception) {
        $output = 'Error: '.$exception->getMessage();
    }

    printf("  %-6s %-20s => %s\n", $verb->value, $path, $output);
}

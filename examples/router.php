<?php

declare(strict_types=1);

/*
 * Builds a route table from #[Route] attributes, then dispatches requests to the controller methods.
 * Run it with: php examples/router.php
 */

use Refract\Examples\Routing\Controllers\UserController;
use Refract\Examples\Routing\HttpMethod;
use Refract\Examples\Routing\InvalidRoute;
use Refract\Examples\Routing\Router;
use Refract\Examples\Routing\RouteNotFound;
use Refract\Examples\Routing\RouteScanner;

require __DIR__.'/../vendor/autoload.php';

$routes = (new RouteScanner())->scan(UserController::class);

echo "Routes:\n";

foreach ($routes as $route) {
    printf("  %-6s %-24s %s::%s()\n", $route->verb->value, $route->path, $route->controller, $route->action);
}

$router = new Router($routes);

echo "\nRequests:\n";

$requests = [
    [HttpMethod::Get, '/users'],
    [HttpMethod::Get, '/users/42'],
    [HttpMethod::Get, '/users/42/posts'],
    [HttpMethod::Get, '/users/42/posts/3'],
    [HttpMethod::Post, '/users/import'],
    [HttpMethod::Get, '/users/abc'],
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

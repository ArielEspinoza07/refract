<?php

declare(strict_types=1);

use Refract\Examples\Routing\Controllers\UserController;
use Refract\Examples\Routing\HttpMethod;
use Refract\Examples\Routing\InvalidRoute;
use Refract\Examples\Routing\RouteDefinition;
use Refract\Examples\Routing\RouteNotFound;
use Refract\Examples\Routing\Router;
use Refract\Examples\Routing\RouteScanner;
use Refract\Tests\Fixtures\Routing\ConflictingController;
use Refract\Tests\Fixtures\Routing\MisconfiguredController;

function router(): Router
{
    $router = new Router((new RouteScanner())->scan(UserController::class, MisconfiguredController::class));
    $router->add(HttpMethod::Get, '/hello/{name}', static fn (string $name): string => "Hello, {$name}!");
    $router->add(HttpMethod::Get, '/sum/{a}/{b}', static fn (int $a, int $b): int => $a + $b);

    return $router;
}

describe('RouteScanner', function (): void {
    it('builds a route for every #[Route] on public methods, prefixed by #[Prefix]', function (): void {
        $routes = array_map(
            static fn (RouteDefinition $route): string => "{$route->signature()} {$route->handlerName()}",
            (new RouteScanner())->scan(UserController::class),
        );
        $controller = UserController::class;

        expect($routes)->toBe([
            "GET /users {$controller}::index()",
            "GET /users/{id} {$controller}::show()",
            "GET /users/{id}/posts {$controller}::posts()",
            "GET /users/{id}/posts/{page} {$controller}::posts()",
            "POST /users {$controller}::store()",
            "POST /users/import {$controller}::store()",
        ]);
    });

    it('points each route to its controller method, and works without #[Prefix]', function (): void {
        $routes = (new RouteScanner())->scan(MisconfiguredController::class);

        expect($routes)->toHaveCount(1)
            ->and($routes[0]->signature())->toBe('GET /broken')
            ->and($routes[0]->handler)->toBe([MisconfiguredController::class, 'broken']);
    });

    it('returns no routes when given no controllers', function (): void {
        expect((new RouteScanner())->scan())->toBe([]);
    });
});

describe('RouteDefinition', function (): void {
    it('matches a verb and path, returning the placeholder values', function (): void {
        $route = new RouteDefinition(HttpMethod::Get, '/users/{id}/posts/{page}', [UserController::class, 'posts']);

        expect($route->match(HttpMethod::Get, '/users/42/posts/3'))->toBe(['id' => '42', 'page' => '3'])
            ->and($route->match(HttpMethod::Post, '/users/42/posts/3'))->toBeNull()
            ->and($route->match(HttpMethod::Get, '/users/42/posts'))->toBeNull()
            ->and($route->match(HttpMethod::Get, '/users/42/posts/3/extra'))->toBeNull();
    });

    it('treats the rest of the path literally', function (): void {
        $route = new RouteDefinition(HttpMethod::Get, '/files/report.pdf', [UserController::class, 'index']);

        expect($route->match(HttpMethod::Get, '/files/report.pdf'))->toBe([])
            ->and($route->match(HttpMethod::Get, '/files/reportXpdf'))->toBeNull();
    });

    it('names its handler, whether a controller method or a closure', function (): void {
        expect((new RouteDefinition(HttpMethod::Get, '/', [UserController::class, 'index']))->handlerName())
            ->toBe(UserController::class.'::index()')
            ->and((new RouteDefinition(HttpMethod::Get, '/', static fn (): string => 'OK'))->handlerName())
            ->toContain('{closure');
    });
});

describe('Router', function (): void {
    it('lists its routes in the order they were registered', function (): void {
        $signatures = array_map(static fn (RouteDefinition $route): string => $route->signature(), router()->routes());

        expect($signatures)->toHaveCount(9)
            ->and($signatures[0])->toBe('GET /users')
            ->and(array_slice($signatures, -2))->toBe(['GET /hello/{name}', 'GET /sum/{a}/{b}']);
    });

    it('dispatches to the controller method of the matching route', function (): void {
        expect(router()->dispatch(HttpMethod::Get, '/users'))->toBe('All users')
            ->and(router()->dispatch(HttpMethod::Post, '/users/import'))->toBe('User stored');
    });

    it('dispatches to a closure route', function (): void {
        expect(router()->dispatch(HttpMethod::Get, '/hello/ada'))->toBe('Hello, ada!');
    });

    it('passes path values to the parameters with the same name, converted to their type', function (): void {
        expect(router()->dispatch(HttpMethod::Get, '/users/42'))->toBe('User #42')
            ->and(router()->dispatch(HttpMethod::Get, '/users/42/posts/3'))->toBe('Posts of user #42, page 3')
            ->and(router()->dispatch(HttpMethod::Get, '/sum/2/40'))->toBe(42);
    });

    it('uses the default value of a parameter that is not in the path', function (): void {
        expect(router()->dispatch(HttpMethod::Get, '/users/42/posts'))->toBe('Posts of user #42, page 1');
    });

    it('rejects a path value that does not fit the parameter type', function (string $path, string $parameter): void {
        expect(static fn (): mixed => router()->dispatch(HttpMethod::Get, $path))
            ->toThrow(InvalidRoute::class, "is not a valid int for \${$parameter}.");
    })->with([
        'controller method' => ['/users/abc', 'id'],
        'closure' => ['/sum/2/x', 'b'],
    ]);

    it('rejects a required parameter that has no path value', function (): void {
        router()->dispatch(HttpMethod::Get, '/broken');
    })->throws(InvalidRoute::class, 'No path value for $id');

    it('throws RouteNotFound when no route matches', function (): void {
        router()->dispatch(HttpMethod::Delete, '/users/42');
    })->throws(RouteNotFound::class, 'No route matches [DELETE /users/42].');

    it('rejects two controller routes with the same verb and path', function (): void {
        new Router((new RouteScanner())->scan(UserController::class, ConflictingController::class));
    })->throws(InvalidRoute::class, '[GET /users/{id}] is declared by both '.UserController::class.'::show() and '.ConflictingController::class.'::find()');

    it('rejects a closure route that duplicates a controller route', function (): void {
        router()->add(HttpMethod::Get, '/users/{id}', static fn (int $id): string => "Other #{$id}");
    })->throws(InvalidRoute::class, '[GET /users/{id}] is declared by both '.UserController::class.'::show() and {closure');
});

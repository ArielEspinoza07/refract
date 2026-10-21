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
    return new Router((new RouteScanner())->scan(UserController::class, MisconfiguredController::class));
}

describe('RouteScanner', function (): void {
    it('builds a route for every #[Route] on public methods, prefixed by #[Prefix]', function (): void {
        $routes = array_map(
            static fn (RouteDefinition $route): string => "{$route->signature()} {$route->action}",
            (new RouteScanner())->scan(UserController::class),
        );

        expect($routes)->toBe([
            'GET /users index',
            'GET /users/{id} show',
            'GET /users/{id}/posts posts',
            'GET /users/{id}/posts/{page} posts',
            'POST /users store',
            'POST /users/import store',
        ]);
    });

    it('ignores routes on methods that are not public, and works without #[Prefix]', function (): void {
        $routes = (new RouteScanner())->scan(MisconfiguredController::class);

        expect(array_map(static fn (RouteDefinition $route): string => $route->signature(), $routes))->toBe(['GET /broken'])
            ->and($routes[0]->controller)->toBe(MisconfiguredController::class);
    });

    it('rejects two routes with the same verb and path', function (): void {
        (new RouteScanner())->scan(UserController::class, ConflictingController::class);
    })->throws(InvalidRoute::class, '[GET /users/{id}] is declared by both '.UserController::class.'::show() and '.ConflictingController::class.'::find()');

    it('returns no routes when given no controllers', function (): void {
        expect((new RouteScanner())->scan())->toBe([]);
    });
});

describe('RouteDefinition', function (): void {
    it('matches a verb and path, returning the placeholder values', function (): void {
        $route = new RouteDefinition(HttpMethod::Get, '/users/{id}/posts/{page}', UserController::class, 'posts');

        expect($route->match(HttpMethod::Get, '/users/42/posts/3'))->toBe(['id' => '42', 'page' => '3'])
            ->and($route->match(HttpMethod::Post, '/users/42/posts/3'))->toBeNull()
            ->and($route->match(HttpMethod::Get, '/users/42/posts'))->toBeNull()
            ->and($route->match(HttpMethod::Get, '/users/42/posts/3/extra'))->toBeNull();
    });

    it('treats the rest of the path literally', function (): void {
        $route = new RouteDefinition(HttpMethod::Get, '/files/report.pdf', UserController::class, 'index');

        expect($route->match(HttpMethod::Get, '/files/report.pdf'))->toBe([])
            ->and($route->match(HttpMethod::Get, '/files/reportXpdf'))->toBeNull();
    });
});

describe('Router', function (): void {
    it('dispatches to the controller method of the matching route', function (): void {
        expect(router()->dispatch(HttpMethod::Get, '/users'))->toBe('All users')
            ->and(router()->dispatch(HttpMethod::Post, '/users/import'))->toBe('User stored');
    });

    it('passes path values to the parameters with the same name, converted to their type', function (): void {
        expect(router()->dispatch(HttpMethod::Get, '/users/42'))->toBe('User #42')
            ->and(router()->dispatch(HttpMethod::Get, '/users/42/posts/3'))->toBe('Posts of user #42, page 3');
    });

    it('uses the default value of a parameter that is not in the path', function (): void {
        expect(router()->dispatch(HttpMethod::Get, '/users/42/posts'))->toBe('Posts of user #42, page 1');
    });

    it('rejects a path value that does not fit the parameter type', function (): void {
        router()->dispatch(HttpMethod::Get, '/users/abc');
    })->throws(InvalidRoute::class, 'Path value [abc] is not a valid int for $id.');

    it('rejects a required parameter that has no path value', function (): void {
        router()->dispatch(HttpMethod::Get, '/broken');
    })->throws(InvalidRoute::class, 'No path value for $id');

    it('throws RouteNotFound when no route matches', function (): void {
        router()->dispatch(HttpMethod::Delete, '/users/42');
    })->throws(RouteNotFound::class, 'No route matches [DELETE /users/42].');
});

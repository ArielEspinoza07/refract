<?php

declare(strict_types=1);

namespace Refract\Tests\Fixtures\Routing;

use Refract\Examples\Routing\Attributes\Prefix;
use Refract\Examples\Routing\Attributes\Route;
use Refract\Examples\Routing\HttpMethod;

/**
 * Declares GET /users/{id} again, which the example's UserController already handles.
 */
#[Prefix('/users')]
final class ConflictingController
{
    #[Route(HttpMethod::Get, '/{id}')]
    public function find(int $id): string
    {
        return "Found #{$id}";
    }
}

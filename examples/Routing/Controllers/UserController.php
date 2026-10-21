<?php

declare(strict_types=1);

namespace Refract\Examples\Routing\Controllers;

use Refract\Examples\Routing\Attributes\Prefix;
use Refract\Examples\Routing\Attributes\Route;
use Refract\Examples\Routing\HttpMethod;

#[Prefix('/users')]
final class UserController
{
    #[Route(HttpMethod::Get)]
    public function index(): string
    {
        return 'All users';
    }

    #[Route(HttpMethod::Get, '/{id}')]
    public function show(int $id): string
    {
        return "User #{$id}";
    }

    #[Route(HttpMethod::Get, '/{id}/posts')]
    #[Route(HttpMethod::Get, '/{id}/posts/{page}')]
    public function posts(int $id, int $page = 1): string
    {
        return "Posts of user #{$id}, page {$page}";
    }

    #[Route(HttpMethod::Post)]
    #[Route(HttpMethod::Post, '/import')]
    public function store(): string
    {
        return 'User stored';
    }

    /**
     * Public, but not an action: it has no #[Route].
     */
    public function format(string $name): string
    {
        return ucfirst($name);
    }
}

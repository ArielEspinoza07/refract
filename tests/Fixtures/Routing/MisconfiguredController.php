<?php

declare(strict_types=1);

namespace Refract\Tests\Fixtures\Routing;

use Refract\Examples\Routing\Attributes\Route;
use Refract\Examples\Routing\HttpMethod;

/**
 * Routes that cannot be dispatched as declared.
 */
final class MisconfiguredController
{
    /**
     * The path has no {id} placeholder, and $id has no default value.
     */
    #[Route(HttpMethod::Get, '/broken')]
    public function broken(int $id): string
    {
        return "Never reached: {$id}";
    }

    public function callHidden(): string
    {
        return $this->hidden();
    }

    /**
     * Private, so it is never routed.
     */
    #[Route(HttpMethod::Get, '/hidden')]
    private function hidden(): string
    {
        return 'Never routed';
    }
}

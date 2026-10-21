<?php

declare(strict_types=1);

namespace Refract\Tests\Fixtures;

/**
 * Inside a trait, PHP cannot resolve self and parent at compile time,
 * so reflection reports them literally instead of as class names.
 */
trait ReturnsRelativeTypes
{
    public function selfReturn(): self
    {
        return $this;
    }

    public function parentReturn(): parent
    {
        return $this;
    }
}

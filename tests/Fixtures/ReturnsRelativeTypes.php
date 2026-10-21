<?php

declare(strict_types=1);

namespace Refract\Tests\Fixtures;

use Countable;

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

    /**
     * Lists the same class twice before another one, since a trait keeps `self` literal and in order.
     */
    public function duplicateBeforeOther(): TypeSamples|self|Countable
    {
        return $this;
    }
}
